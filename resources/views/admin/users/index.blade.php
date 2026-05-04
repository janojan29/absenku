<x-app-layout>
    <x-slot name="title">Manajemen User</x-slot>
    <x-slot name="header">
        <h1 class="text-display-sm text-surface-50">Manajemen User</h1>
        <p class="text-sm text-electric-200/80 mt-1">Kelola data siswa, guru, dan admin</p>
    </x-slot>

    <div class="space-y-6">
        {{-- Search & Filter --}}
        <div class="card animate-fade-slide-up">
            <form method="GET" action="{{ route('admin.users.index') }}" class="filter-panel filter-form">
                <div class="filter-search-wrap">
                    <svg class="filter-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        class="filter-input"
                        placeholder="Cari nama, email, NISN, NIP..."
                    >
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-primary btn-ripple px-5 h-[42px]">
                        Cari
                    </button>
                    @if ($q !== '')
                        <a href="{{ route('admin.users.index') }}" class="btn-secondary px-5 h-[42px] flex items-center justify-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Users Table --}}
        <div class="table-wrapper animate-fade-slide-up stagger-1">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="table-header">
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">User</th>
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Role</th>
                            <th class="text-left py-3.5 px-4 text-xs font-semibold uppercase tracking-wider hidden md:table-cell">Identitas</th>
                            <th class="text-right py-3.5 px-4 text-xs font-semibold uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="table-row">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-navy-500/20 to-navy-600/20 flex items-center justify-center shrink-0">
                                            <span class="text-sm font-bold text-navy-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-navy-800 text-sm truncate">{{ $user->name }}</div>
                                            <div class="text-xs text-bw-400 truncate">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $roleName = $user->getRoleNames()->first();
                                        $roleLabel = match ($roleName) {
                                            'petugas_piket' => 'Petugas Piket',
                                            'guru_walikelas' => 'Guru Walikelas',
                                            default => ucfirst(str_replace('_', ' ', $roleName ?? '-')),
                                        };
                                        $badgeClass = match($roleName) {
                                            'siswa' => 'badge-hadir',
                                            'guru', 'guru_walikelas' => 'badge-terlambat',
                                            'admin' => 'bg-navy-100 text-navy-700 border-navy-200',
                                            default => 'badge-ijin'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        {{ $roleLabel }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 hidden md:table-cell">
                                    @if ($user->studentProfile)
                                        <div class="text-sm text-navy-800">NISN: <span class="font-medium">{{ $user->studentProfile->nis ?? '-' }}</span></div>
                                        <div class="text-xs text-bw-400">{{ $user->studentProfile->classRoom?->name ?? '-' }}</div>
                                    @elseif ($user->teacher && $user->hasAnyRole(['guru', 'guru_walikelas']))
                                        <div class="text-sm text-navy-800">NIP: <span class="font-medium">{{ $user->teacher->nip ?? '-' }}</span></div>
                                        <div class="text-xs text-bw-400">{{ $user->teacher->subject ?? '-' }}</div>
                                    @else
                                        <span class="text-bw-300">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @php
                                        $canDelete = in_array($roleName, ['guru', 'guru_walikelas', 'siswa'], true) && auth()->id() !== $user->id;
                                    @endphp
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" onclick="openEditModal(event, {{ $user->id }})" class="btn-secondary h-8 px-3 text-xs gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.89.11l-3.15-.8a.8.8 0 0 1-.505-.505l.8-3.15a4.5 4.5 0 0 1 1.11-1.89l12.42-12.42Zm8.25-2.257a2.25 2.25 0 0 1-3.182 0l-1.06-1.06a2.25 2.25 0 0 1 0-3.182l1.06-1.06a2.25 2.25 0 0 1 3.182 0l1.06 1.06a2.25 2.25 0 0 1 0 3.182l-1.06 1.06Zm-2.25 2.257-2.12-2.12"/></svg>
                                            Edit
                                        </button>

                                        @if($canDelete)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Hapus User', message: 'Hapus user ini? Tindakan ini tidak bisa dibatalkan.', confirmText: 'Ya, Hapus', type: 'danger', formEl: this } }));" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger h-8 px-3 text-xs gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                Hapus
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-12 text-center text-bw-400">Tidak ada data user.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="mt-4 px-4 pb-4">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="editModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6">
        <div class="fixed inset-0 bg-navy-950/40 backdrop-blur-sm transition-opacity" onclick="closeEditModal()"></div>
        
        <div class="bg-white rounded-2xl shadow-card w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col relative z-10 animate-fade-scale-in">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-bw-200 flex items-center justify-between bg-bw-50/50">
                <h3 class="font-bold text-lg text-navy-900">Edit Data User</h3>
                <button type="button" onclick="closeEditModal()" class="w-8 h-8 flex items-center justify-center rounded-lg text-bw-400 hover:text-navy-600 hover:bg-bw-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form id="editForm" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    {{-- Grid 2 Kolom --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                            <input type="text" name="name" id="editName" class="form-input-clean w-full" required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Role Akses</label>
                            <input type="hidden" name="role" id="editRoleHidden" value="">
                            <select name="role_select" id="editRole" class="form-select w-full" onchange="syncRole(); updateFieldsVisibility();" required>
                                <option value="">-- Pilih Role --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">
                                        {{ $role->name === 'petugas_piket' ? 'Petugas Piket' : ($role->name === 'guru_walikelas' ? 'Guru Walikelas' : ucfirst(str_replace('_', ' ', $role->name))) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">No. WhatsApp</label>
                            <input type="text" name="whatsapp_number" id="editWhatsapp" class="form-input-clean w-full" placeholder="+628...">
                        </div>
                    </div>

                    {{-- Dynamic Student Fields --}}
                    <div id="studentFields" class="hidden space-y-5 p-5 rounded-xl bg-bw-50 border border-bw-200">
                        <h4 class="font-bold text-navy-800 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-navy-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342"/></svg>
                            Data Siswa
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">NISN <span class="text-red-500">*</span></label>
                                <input type="text" name="nis" id="editNis" class="form-input-clean w-full">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Kelas <span class="text-red-500">*</span></label>
                                <select name="class_room_id" id="editClassRoom" class="form-select w-full">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($classRooms as $room)
                                        <option value="{{ $room->id }}">{{ $room->jurusan ? $room->name.' : '.$room->jurusan : $room->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">No. WA Orang Tua</label>
                                <input type="text" name="parent_phone_wa" id="editParentPhone" class="form-input-clean w-full" placeholder="+628...">
                            </div>
                        </div>
                    </div>

                    {{-- Dynamic Teacher Fields --}}
                    <div id="teacherFields" class="hidden space-y-5 p-5 rounded-xl bg-bw-50 border border-bw-200">
                        <h4 class="font-bold text-navy-800 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-navy-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            Data Guru
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">NIP <span class="text-red-500">*</span></label>
                                <input type="text" name="nip" id="editNip" class="form-input-clean w-full">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Mata Pelajaran</label>
                                <input type="text" name="subject" id="editSubject" class="form-input-clean w-full">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Ket. Wali Kelas</label>
                                <input type="text" name="wali_kelas" id="editWaliKelas" class="form-input-clean w-full" placeholder="Cth: X IPA 1">
                            </div>
                        </div>
                    </div>

                    {{-- Password Fields --}}
                    <div class="space-y-4">
                        <h4 class="font-bold text-navy-800 text-sm border-b border-bw-200 pb-2">Ubah Password <span class="font-normal text-bw-400 text-xs ml-2">(Opsional)</span></h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Password Baru</label>
                                <input type="password" name="password" id="editPassword" class="form-input-clean w-full" placeholder="Minimal 8 karakter">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" id="editPasswordConfirmation" class="form-input-clean w-full" placeholder="Ulangi password">
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-6 border-t border-bw-200 flex items-center justify-end gap-3 sticky bottom-0 bg-white">
                        <button type="button" onclick="closeEditModal()" class="btn-secondary px-6 h-10">Batal</button>
                        <button type="submit" class="btn-primary btn-ripple px-6 h-10">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(e, userId) {
            e.preventDefault();
            
            fetch(`/admin/users/${userId}`)
                .then(response => response.json())
                .then(user => {
                    populateForm(user);
                    const modal = document.getElementById('editModal');
                    modal.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Gagal memuat data user', type: 'error' }}));
                });
        }

        function populateForm(user) {
            document.getElementById('editName').value = user.name || '';
            document.getElementById('editRole').value = user.roles && user.roles.length > 0 ? user.roles[0].name : '';
            document.getElementById('editRoleHidden').value = document.getElementById('editRole').value;
            document.getElementById('editWhatsapp').value = user.whatsapp_number || '';

            document.getElementById('editPassword').value = '';
            document.getElementById('editPasswordConfirmation').value = '';

            if (user.student_profile) {
                document.getElementById('editNis').value = user.student_profile.nis || '';
                document.getElementById('editClassRoom').value = user.student_profile.class_room_id || '';
                document.getElementById('editParentPhone').value = user.student_profile.parent_phone_wa || '';
            } else {
                document.getElementById('editNis').value = '';
                document.getElementById('editClassRoom').value = '';
                document.getElementById('editParentPhone').value = '';
            }

            if (user.teacher) {
                document.getElementById('editNip').value = user.teacher.nip || '';
                document.getElementById('editSubject').value = user.teacher.subject || '';
                document.getElementById('editWaliKelas').value = user.teacher.wali_kelas || '';
            } else {
                document.getElementById('editNip').value = '';
                document.getElementById('editSubject').value = '';
                document.getElementById('editWaliKelas').value = '';
            }

            document.getElementById('editForm').action = `/admin/users/${user.id}`;
            updateFieldsVisibility();
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function syncRole() {
            document.getElementById('editRoleHidden').value = document.getElementById('editRole').value;
        }

        function updateFieldsVisibility() {
            const role = document.getElementById('editRole').value;
            const studentFields = document.getElementById('studentFields');
            const teacherFields = document.getElementById('teacherFields');
            const roleSelect = document.getElementById('editRole');
            const nameInput = document.getElementById('editName');
            const whatsappInput = document.getElementById('editWhatsapp');

            const isProtected = ['admin', 'petugas_piket'].includes(role);

            nameInput.disabled = isProtected;
            whatsappInput.disabled = isProtected;
            roleSelect.disabled = isProtected;

            studentFields.classList.toggle('hidden', isProtected || role !== 'siswa');
            teacherFields.classList.toggle('hidden', isProtected || !['guru', 'guru_walikelas'].includes(role));

            const classInput = document.getElementById('editClassRoom');
            if (classInput) classInput.required = !isProtected && role === 'siswa';
            
            const waliKelasInput = document.getElementById('editWaliKelas');
            if (waliKelasInput) waliKelasInput.required = !isProtected && role === 'guru_walikelas';
        }
    </script>
</x-app-layout>
