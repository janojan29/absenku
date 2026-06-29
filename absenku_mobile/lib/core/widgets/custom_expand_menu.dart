import 'package:flutter/material.dart';
import '../config/theme.dart';

class CustomExpandMenu extends StatelessWidget {
  final String title;
  final String subtitle;
  final List<Map<String, String>> items;
  final String selectedValue;
  final Function(String) onChanged;

  const CustomExpandMenu({
    super.key,
    required this.title,
    required this.subtitle,
    required this.items,
    required this.selectedValue,
    required this.onChanged,
  });

  void _showBottomSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: items.length > 6 ? 0.6 : 0.45,
          minChildSize: 0.3,
          maxChildSize: 0.85,
          expand: false,
          builder: (context, scrollController) {
            return SafeArea(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const SizedBox(height: 10),
                  // Drag Handle
                  Container(
                    width: 38,
                    height: 4.5,
                    decoration: BoxDecoration(
                      color: Colors.grey[300],
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Header Title
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          title,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.primaryNavy,
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, size: 20, color: AppTheme.textMuted),
                          onPressed: () => Navigator.pop(context),
                          constraints: const BoxConstraints(),
                          padding: EdgeInsets.zero,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                  const Divider(height: 1),
                  Expanded(
                    child: ListView.builder(
                      controller: scrollController,
                      itemCount: items.length,
                      itemBuilder: (context, index) {
                        final item = items[index];
                        final isSelected = item['value'] == selectedValue;
                        return InkWell(
                          onTap: () {
                            onChanged(item['value']!);
                            Navigator.pop(context);
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                            decoration: BoxDecoration(
                              color: isSelected ? AppTheme.primaryBlue.withValues(alpha: 0.04) : Colors.transparent,
                              border: Border(bottom: BorderSide(color: Colors.grey[100]!, width: 0.8)),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  isSelected ? Icons.radio_button_checked : Icons.radio_button_unchecked,
                                  color: isSelected ? AppTheme.primaryBlue : Colors.grey[400],
                                  size: 22,
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Text(
                                    item['label']!,
                                    style: TextStyle(
                                      fontSize: 14,
                                      fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                                      color: isSelected ? AppTheme.primaryBlue : AppTheme.textDark,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => _showBottomSheet(context),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppTheme.borderLight, width: 1.5),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(title, style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                  const SizedBox(height: 4),
                  Text(
                    subtitle.isNotEmpty ? subtitle : '-',
                    style: const TextStyle(fontSize: 13, color: AppTheme.textDark, fontWeight: FontWeight.bold),
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            const Icon(Icons.keyboard_arrow_down, color: AppTheme.textMuted, size: 20),
          ],
        ),
      ),
    );
  }
}
