<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService; // Import Service
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    protected $userService;

    /**
     * Dependency Injection UserService.
     * Laravel otomatis menyuntikkan instance UserService ke sini.
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Menampilkan daftar user.
     */
    public function index()
    {
        // Panggil method di service untuk ambil data paginasi
        $users = $this->userService->getPaginatedUsers(10);

        // Pastikan nama folder view sesuai dengan struktur folder Anda
        // Jika foldernya resources/views/admin/user/index.blade.php, ubah jadi 'admin.user.index'
        return view('admin.user-management.index', compact('users'));
    }

    /**
     * Menampilkan form tambah user.
     */
    public function create()
    {
        return view('admin.user-management.create');
    }

    /**
     * Menyimpan data user baru.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input (Tugas Controller)
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'username'  => 'required|string|unique:users,username',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'role'      => 'required|in:admin,uptd,admin_pangan,admin_hartibun',
            'status'    => 'required|in:aktif,nonaktif',
        ]);

        // 2. Panggil Service untuk logika penyimpanan (Hashing password dll)
        $this->userService->createUser($validated);

        // 3. Redirect
        return redirect()->route('admin.user-management.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit user.
     */
    public function edit($id)
    {
        // Ambil data user via service
        $user = $this->userService->getUserById($id);

        return view('admin.user-management.edit', compact('user'));
    }

    /**
     * Mengupdate data user.
     */
    public function update(Request $request, $id)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            // Ignore unique validation untuk ID user yang sedang diedit
            'username'  => 'required|string|unique:users,username,' . $id,
            'email'     => 'required|email|unique:users,email,' . $id,
            'password'  => 'nullable|min:6', 
            'role'      => 'required|in:admin,uptd, admin_pangan, admin_hartibun',
            'status'    => 'required|in:aktif,nonaktif',
        ]);

        // 2. Ambil User lama & Update via Service
        $user = $this->userService->getUserById($id);
        $this->userService->updateUser($user, $validated);

        return redirect()->route('admin.user-management.index')
            ->with('success', 'Data user berhasil diperbarui.');
    }

    /**
     * Menghapus user.
     */
    public function destroy($id)
    {
        try {
            $user = $this->userService->getUserById($id);
            
            // Panggil delete di service (Service akan menolak jika hapus akun sendiri)
            $this->userService->deleteUser($user);

            return redirect()->route('admin.user-management.index')
                ->with('success', 'User berhasil dihapus.');

        } catch (\Exception $e) {
            // Tangkap error dari Service (misal: hapus akun sendiri)
            return back()->with('error', $e->getMessage());
        }
    }
}