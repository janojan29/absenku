// lib/widgets/app_scaffold.dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../core/app_colors.dart';
import '../providers/auth_provider.dart';

class AppScaffold extends StatelessWidget {
  final Widget child;
  final String title;

  const AppScaffold({
    super.key,
    required this.child,
    required this.title,
  });

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final user = auth.currentUser;
    final role = user?.role ?? 'siswa';

    List<NavigationItem> navItems = [];
    if (role == 'admin') {
      navItems = [
        NavigationItem(icon: Icons.settings, label: 'Pengaturan', route: '/admin/settings'),
        NavigationItem(icon: Icons.people, label: 'User', route: '/admin/users'),
        NavigationItem(icon: Icons.school, label: 'Siswa', route: '/admin/students'),
        NavigationItem(icon: Icons.badge, label: 'Guru', route: '/admin/teachers'),
      ];
    } else if (role == 'guru_walikelas') {
      navItems = [
        NavigationItem(icon: Icons.dashboard, label: 'Dashboard', route: '/teacher/dashboard'),
        NavigationItem(icon: Icons.assessment, label: 'Laporan', route: '/teacher/report'),
      ];
    } else if (role == 'petugas_piket') {
      navItems = [
        NavigationItem(icon: Icons.assignment_turned_in, label: 'Persetujuan', route: '/picket/leave'),
      ];
    } else {
      navItems = [
        NavigationItem(icon: Icons.check_circle, label: 'Presensi', route: '/student/attendance'),
      ];
    }

    final currentRoute = GoRouterState.of(context).matchedLocation;
    int selectedIndex = navItems.indexWhere((item) => item.route == currentRoute);
    if (selectedIndex == -1) selectedIndex = 0;

    return Scaffold(
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
        backgroundColor: AppColors.space900,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.person),
            onPressed: () => context.push('/profile'),
          ),
        ],
      ),
      drawer: Drawer(
        child: Container(
          color: AppColors.space950,
          child: Column(
            children: [
              UserAccountsDrawerHeader(
                decoration: const BoxDecoration(color: AppColors.space900),
                currentAccountPicture: const CircleAvatar(
                  backgroundColor: AppColors.electric600,
                  child: Icon(Icons.person, size: 40, color: Colors.white),
                ),
                accountName: Text(user?.name ?? 'Guest User', style: const TextStyle(fontWeight: FontWeight.bold)),
                accountEmail: Text(user?.email ?? 'guest@absenku.com'),
              ),
              Expanded(
                child: ListView(
                  children: [
                    ...navItems.map((item) {
                      final isSelected = item.route == currentRoute;
                      return ListTile(
                        leading: Icon(item.icon, color: isSelected ? AppColors.electric500 : Colors.white70),
                        title: Text(item.label, style: TextStyle(color: isSelected ? Colors.white : Colors.white70)),
                        selected: isSelected,
                        onTap: () {
                          Navigator.pop(context);
                          context.go(item.route);
                        },
                      );
                    }),
                    const Divider(color: AppColors.space700),
                    ListTile(
                      leading: const Icon(Icons.exit_to_app, color: Colors.redAccent),
                      title: const Text('Keluar', style: TextStyle(color: Colors.redAccent)),
                      onTap: () {
                        auth.logout();
                        context.go('/login');
                      },
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
      body: child,
      bottomNavigationBar: navItems.length > 1
          ? BottomNavigationBar(
              currentIndex: selectedIndex,
              onTap: (index) => context.go(navItems[index].route),
              backgroundColor: Colors.white,
              selectedItemColor: AppColors.electric600,
              unselectedItemColor: AppColors.space500,
              items: navItems.map((item) {
                return BottomNavigationBarItem(
                  icon: Icon(item.icon),
                  label: item.label,
                );
              }).toList(),
            )
          : null,
    );
  }
}

class NavigationItem {
  final IconData icon;
  final String label;
  final String route;

  NavigationItem({
    required this.icon,
    required this.label,
    required this.route,
  });
}
