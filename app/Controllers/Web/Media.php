<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

class Media extends BaseController
{
    public function serve(...$segments)
    {
        $key = implode('/', $segments);
        $s3 = service('s3');
        $bucket = config('S3')->bucket;
        
        try {
            // Check if client is the raw S3 client
            $s3Client = $s3->getClient();
            $result = $s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
            
            // Set headers based on S3 object metadata
            $this->response->setHeader('Content-Type', $result['ContentType']);
            $this->response->setHeader('Content-Length', $result['ContentLength']);
            
            // We use setBody to output the raw body stream
            return $this->response->setBody($result['Body']->getContents());
            
        } catch (\Exception $e) {
            // Return a default image or 404
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }
    }
}
