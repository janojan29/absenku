// File ini berisi helper unduhan file lintas platform.
// Fungsi utamanya adalah menyediakan antarmuka sederhana untuk mengunduh file dari aplikasi di berbagai platform.

import 'package:dio/dio.dart';

import 'download_file_io.dart'
    if (dart.library.js_interop) 'download_file_web.dart';

Future<void> downloadAndOpenFile({
  required Dio dio,
  required String url,
  required String fileName,
}) {
  return downloadAndOpenFileImpl(
    dio: dio,
    url: url,
    fileName: fileName,
  );
}