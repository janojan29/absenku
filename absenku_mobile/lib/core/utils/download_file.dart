import 'package:dio/dio.dart';

import 'download_file_io.dart'
    if (dart.library.html) 'download_file_web.dart';

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