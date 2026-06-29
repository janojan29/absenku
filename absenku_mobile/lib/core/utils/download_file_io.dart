import 'package:dio/dio.dart';
import 'package:open_file/open_file.dart';
import 'package:path_provider/path_provider.dart';

Future<void> downloadAndOpenFileImpl({
  required Dio dio,
  required String url,
  required String fileName,
}) async {
  final directory = await getTemporaryDirectory();
  final savePath = '${directory.path}/$fileName';

  await dio.download(url, savePath);
  await OpenFile.open(savePath);
}