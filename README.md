# Amazon-S3
Class for usual operations in Amazon S3 (upload, delete, download file) + generate presigned link

Class can be instantiate like that:


try
{
		$s3 = new Amazon();
		$s3->generatePresignedUrl('MyBucket','test.pdf'); // generate a link for direct access to specific file
		$s3->uploadFile('MyBucket','s3/test.jpg');
		$s3->downloadFile('MyBucket','s3/test.jpg');
		$s3->deleteFile('MyBucket','s3/test.jpg');
}
catch (\EP $e) { echo "An error ocurred! Error number: #". $e->getMessage(); }
catch (\Exception $e) { echo $e->getMessage(); }

My code uses for catch class \EP witch is not present in this project so feel free to change thrown exception \EP in anything you want :)
