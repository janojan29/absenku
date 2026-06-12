<x-app-layout>
    <x-slot name="title">Manajemen Pengguna</x-slot>
    <x-slot name="header">
        <h1 class="text-display-sm text-surface-50">Manajemen Pengguna</h1>
        <p class="text-sm text-electric-200/80 mt-1">Kelola data siswa, guru, dan admin</p>
    </x-slot>

    <div class="space-y-6">
        <livewire:admin.user-table />
    </div>

    {{-- Edit Modal --}}
    <div id="editModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6">
        <div class="fixed inset-0 bg-navy-950/40 backdrop-blur-sm transition-opacity" onclick="closeEditModal()"></div>
        
        <form id="editForm" method="POST" class="bg-white rounded-2xl shadow-card w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col relative z-10 animate-fade-scale-in">
            @csrf
            @method('PATCH')
            
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-bw-200 flex items-center justify-between bg-bw-50/50">
                <h3 class="font-bold text-lg text-navy-900">Edit Data Pengguna</h3>
                <button type="button" onclick="closeEditModal()" class="w-8 h-8 flex items-center justify-center rounded-lg text-bw-400 hover:text-navy-600 hover:bg-bw-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
                {{-- Grid 2 Kolom --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" id="editName" class="form-input-clean w-full" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Peran Akses</label>
                        <input type="hidden" name="role" id="editRoleHidden" value="">
                        <select name="role_select" id="editRole" class="form-select w-full" onchange="syncRole(); updateFieldsVisibility();" required>
                            <option value="">-- Pilih Peran --</option>
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
                            <div class="relative">
                                <input type="password" name="password" id="editPassword" class="form-input-clean w-full pr-10" placeholder="Minimal 8 karakter" autocomplete="new-password">
                                <button type="button" onclick="togglePasswordField('editPassword', this)" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-bw-400 hover:text-navy-500 focus:outline-none" aria-label="Lihat password">
                                    <svg class="eye-open w-4.5 h-4.5" style="display:none" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    <svg class="eye-closed w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-navy-600 uppercase tracking-wider mb-1.5">Konfirmasi Password</label>
                            <div class="relative">
                                <input type="password" name="password_confirmation" id="editPasswordConfirmation" class="form-input-clean w-full pr-10" placeholder="Ulangi password" autocomplete="new-password">
                                <button type="button" onclick="togglePasswordField('editPasswordConfirmation', this)" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-bw-400 hover:text-navy-500 focus:outline-none" aria-label="Lihat konfirmasi password">
                                    <svg class="eye-open w-4.5 h-4.5" style="display:none" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    <svg class="eye-closed w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 border-t border-bw-200 flex items-center justify-end gap-3 bg-bw-50/50">
                <button type="button" onclick="closeEditModal()" class="btn-secondary px-6 h-10">Batal</button>
                <button type="submit" class="btn-primary btn-ripple px-6 h-10">Simpan Perubahan</button>
            </div>
        </form>
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
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Gagal memuat data pengguna', type: 'error' }}));
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

        function togglePasswordField(inputId, trigger) {
            const input = document.getElementById(inputId);
            if (!input) {
                return;
            }

            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            trigger.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Lihat password');

            const eyeOpen = trigger.querySelector('.eye-open');
            const eyeClosed = trigger.querySelector('.eye-closed');
            if (eyeOpen && eyeClosed) {
                eyeOpen.style.display = showPassword ? '' : 'none';
                eyeClosed.style.display = showPassword ? 'none' : '';
            }
        }

        function updateFieldsVisibility() {
            const role = document.getElementById('editRole').value;
            const studentFields = document.getElementById('studentFields');
            const teacherFields = document.getElementById('teacherFields');
            const roleSelect = document.getElementById('editRole');
            const nameInput = document.getElementById('editName');
            const whatsappInput = document.getElementById('editWhatsapp');

            const isRestrictedProfile = ['admin'].includes(role);
            const isLockedRole = ['admin', 'petugas_piket'].includes(role);

            nameInput.disabled = isRestrictedProfile;
            whatsappInput.disabled = isRestrictedProfile;
            roleSelect.disabled = isLockedRole;

            studentFields.classList.toggle('hidden', isRestrictedProfile || role !== 'siswa');
            teacherFields.classList.toggle('hidden', isRestrictedProfile || !['guru', 'guru_walikelas'].includes(role));

            const classInput = document.getElementById('editClassRoom');
            if (classInput) classInput.required = !isRestrictedProfile && role === 'siswa';
            
            const waliKelasInput = document.getElementById('editWaliKelas');
            if (waliKelasInput) waliKelasInput.required = !isRestrictedProfile && role === 'guru_walikelas';

            const passwordInput = document.getElementById('editPassword');
            const passwordConfirmationInput = document.getElementById('editPasswordConfirmation');
            if (passwordInput) {
                passwordInput.disabled = isRestrictedProfile;
                passwordInput.type = 'password';
            }
            if (passwordConfirmationInput) {
                passwordConfirmationInput.disabled = isRestrictedProfile;
                passwordConfirmationInput.type = 'password';
            }
        }
    </script>
</x-app-layout>
