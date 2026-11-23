<?php

namespace App\Http\Controllers;

use App\Models\CampusMeetingPoint;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string)$request->query('q', ''));
        $roleFilter = (string)$request->query('role', '');
        $campusFilter = (string)$request->query('campus', '');
        $pointSearch = trim((string)$request->query('point_q', ''));
        $productSearch = trim((string)$request->query('product_q', ''));
        $productStatusFilter = (string)$request->query('product_status', '');

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'total_admins' => User::where('role', 'admin')->count(),
            'total_meeting_points' => CampusMeetingPoint::count(),
            'active_meeting_points' => CampusMeetingPoint::where('is_active', true)->count(),
            'total_products' => Product::count(),
            'available_products' => Product::where('status', 'available')->count(),
            'total_transactions' => Transaction::count(),
        ];

        $meetingPoints = CampusMeetingPoint::query()
            ->when($campusFilter !== '', fn ($q) => $q->where('campus',$campusFilter))
            ->when($pointSearch !== '', function ($q) use ($pointSearch) {$q->where(function ($sub) use ($pointSearch) {
                    $sub->where('point_name', 'like', "\%{$pointSearch}%")
                        ->orWhere('address_description', 'like', "%{$pointSearch}%")
                        ->orWhere('campus', 'like', "%{$pointSearch}%");
                });
            })
            ->orderBy('campus')
            ->orderBy('point_name')
            ->get();

        $products = Product::query()
            ->with(['seller', 'category', 'media'])
            ->when($productSearch !== '', function ($q) use ($productSearch) {$q->where(function ($sub) use ($productSearch) {
                    $sub->where('product_name', 'like', "\%{$productSearch}%")
                        ->orWhere('description', 'like', "%{$productSearch}%")
                        ->orWhereHas('seller', function ($sellerQ) use ($productSearch) {
                            $sellerQ->where('name', 'like', "\%{$productSearch}%")
                                ->orWhere('email', 'like', "%{$productSearch}%");
                        });
                });
            })
            ->when(in_array($productStatusFilter, ['available', 'sold', 'archived'], true), fn ($q) => $q->where('status',$productStatusFilter))
            ->orderByDesc('product_id')
            ->paginate(10, ['*'], 'products_page')
            ->withQueryString();

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {$q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "\%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('npm', 'like', "%{$search}%");
                });
            })
            ->when(in_array($roleFilter, ['user', 'admin'], true), fn ($q) => $q->where('role',$roleFilter))
            ->orderByDesc('user_id')
            ->paginate(10, ['*'], 'users_page')
            ->withQueryString();

        return view('admin.dashboard', [
            'stats' => $stats,
            'meetingPoints' => $meetingPoints,
            'products' => $products,
            'users' => $users,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'campusFilter' => $campusFilter,
            'pointSearch' => $pointSearch,
            'productSearch' => $productSearch,
            'productStatusFilter' => $productStatusFilter,
        ]);
    }

    public function storeMeetingPoint(Request $request): RedirectResponse
    {
        $validated =$request->validate(
            [
                'campus' => ['required', Rule::in(['UNSIKA', 'UBP'])],
                'point_name' => ['required', 'string', 'max:150'],
                'address_description' => ['required', 'string', 'max:500'],
                'is_active' => ['nullable', 'boolean'],
            ],
            [
                'campus.required' => 'Kampus wajib dipilih.',
                'campus.in' => 'Kampus hanya boleh UNSIKA atau UBP.',
                'point_name.required' => 'Nama titik COD wajib diisi.',
                'address_description.required' => 'Deskripsi lokasi wajib diisi.',
            ]
        );

        CampusMeetingPoint::create([
            'campus' => $validated['campus'],
            'point_name' => trim($validated['point_name']),
            'address_description' => trim($validated['address_description']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Titik COD kampus berhasil ditambahkan.');
    }

    public function updateMeetingPoint(Request $request, CampusMeetingPoint$meetingPoint): RedirectResponse
    {
        $validated =$request->validate(
            [
                'campus' => ['required', Rule::in(['UNSIKA', 'UBP'])],
                'point_name' => ['required', 'string', 'max:150'],
                'address_description' => ['required', 'string', 'max:500'],
                'is_active' => ['nullable', 'boolean'],
            ],
            [
                'campus.required' => 'Kampus wajib dipilih.',
                'point_name.required' => 'Nama titik COD wajib diisi.',
                'address_description.required' => 'Deskripsi lokasi wajib diisi.',
            ]
        );

        $meetingPoint->update([
            'campus' => $validated['campus'],
            'point_name' => trim($validated['point_name']),
            'address_description' => trim($validated['address_description']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Titik COD kampus berhasil diperbarui.');
    }

    public function destroyMeetingPoint(CampusMeetingPoint $meetingPoint): RedirectResponse
    {
        if ($meetingPoint->transactions()->exists()) {$meetingPoint->update(['is_active' => false]);

            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'Titik COD sudah pernah dipakai transaksi sehingga dinonaktifkan (tidak dihapus permanen).');
        }

        $meetingPoint->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Titik COD kampus berhasil dihapus.');
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        $productName =$product->product_name;

        if ($product->transactionItems()->exists()) {$product->update([
                'status' => 'archived',
                'stock' => 0,
            ]);

            return redirect()
                ->route('admin.dashboard')
                ->with('success', "Produk \"{$productName}\" sudah memiliki riwayat transaksi sehingga diarsipkan dan disembunyikan dari katalog.");
        }

        foreach ($product->media as $media) {$path = $media->media_url ?? $media->file_path ?? null;
            if ($path && ! str_starts_with($path, 'http')) {
                Storage::disk('public')->delete(ltrim($path, '/'));
            }
        }

        $product->media()->delete();$product->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Produk \"{$productName}\" berhasil dihapus dari marketplace.");
    }

    public function toggleUserStatus(User $user): RedirectResponse
    {
        if ($user->user_id === Auth::id()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Kamu tidak dapat menonaktifkan akunmu sendiri.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $statusText =$user->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Akun {$user->name} berhasil {$statusText}.");
    }

    public function updateUserRole(Request $request, User$user): RedirectResponse
    {
        $validated =$request->validate([
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);

        if ($user->user_id === Auth::id() &&$validated['role'] !== 'admin') {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Kamu tidak dapat mengubah role akunmu sendiri menjadi user.');
        }

        $user->update([
            'role' => $validated['role'],
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Role {$user->name} berhasil diubah menjadi {$validated['role']}.");
    }
}