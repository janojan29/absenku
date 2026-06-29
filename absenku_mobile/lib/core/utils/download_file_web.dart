// ignore_for_file: deprecated_member_use, avoid_web_libraries_in_flutter

import 'dart:html' as html;
import 'dart:typed_data';

import 'package:dio/dio.dart';

Future<void> downloadAndOpenFileImpl({
  required Dio dio,
  required String url,
  required String fileName,
}) async {
  final response = await dio.get<List<int>>(
    url,
    options: Options(responseType: ResponseType.bytes),
  );

  final bytes = Uint8List.fromList(response.data ?? <int>[]);
  final blob = html.Blob([bytes], _mimeTypeForFileName(fileName));
  final objectUrl = html.Url.createObjectUrlFromBlob(blob);
  final anchor = html.AnchorElement(href: objectUrl)
    ..download = fileName
    ..style.display = 'none';

  html.document.body?.children.add(anchor);
  anchor.click();
  anchor.remove();
  html.Url.revokeObjectUrl(objectUrl);
}

String _mimeTypeForFileName(String fileName) {
  if (fileName.endsWith('.pdf')) {
    return 'application/pdf';
  }

  if (fileName.endsWith('.xlsx')) {
    return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  }

  return 'application/octet-stream';
}