<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Image;
use File;
use Storage;
use Response;
class FileUploadController extends Controller
{
   
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //upload new file
    public function store(Request $request)
    {
        if($request->is_multiple==1)
        {
            $validation = \Validator::make($request->all(),[ 
                'file'     => 'required|array|max:20000|min:1'
            ]);
        }
        else
        {
            $validation = \Validator::make($request->all(),[ 
                'file'     => 'required|max:10000',
            ]);
        }
        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }
        try
        {
            $destinationPath = 'uploads/';
            $fileArray = array();
            $formatCheck = ['doc','docx','png','jpeg','jpg','pdf','svg','mp4','webp','csv'];

            if($request->is_multiple==1)
            {

                $files = $request->file;
                foreach ($files as $key => $file) 
                {
                    $extension = strtolower($file->getClientOriginalExtension());
                    if(!in_array($extension, $formatCheck))
                    {
                        return response()->json(prepareResult(true, [], trans('translate.file_not_allowed').'Only allowed : doc, docx, png, jpeg, jpg, pdf, svg, mp4, gif, webp, csv'), config('httpcodes.internal_server_error'));
                    }

                    //********************************
                    //scan all files
                    $finfo = finfo_open(FILEINFO_MIME_TYPE); // Return MIME type
                    $fileActCheck = finfo_file($finfo, $file);
                    finfo_close($finfo);
                    if($fileActCheck=='application/x-dosexec' || $fileActCheck=='application/x-msdownload')
                    {
                        return response()->json(prepareResult(true, [], trans('translate.malicious_file').'Blocked for security reasons! '), config('httpcodes.internal_server_error'));
                    }
                    //********************************

                    $fileName   = time().'-'.rand(1,999999) . '.' . $file->getClientOriginalExtension();
                    $filePath = 'uploads/' . $fileName;
                    $file->storeAs('public/uploads',$fileName);

                    if(env('APP_ENV', 'local')==='production')
                    {
                        $callApi = secure_url('api/file-access/'.$filePath);
                    }
                    else
                    {
                        $callApi = url('api/file-access/'.$filePath);
                    }
                    
                    $fileArray[] = [
                        'file_name'         => $callApi,
                        'file_extension'    => $file->getClientOriginalExtension(),
                        'uploading_file_name' => $file->getClientOriginalName(),
                    ];
                }

                return response(prepareResult(false, $fileArray, trans('translate.file_uploaded')),config('httpcodes.created'));
            }
            else
            {
                $file      = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                if(!in_array($extension, $formatCheck))
                {
                    return response()->json(prepareResult(true, [], trans('translate.file_not_allowed').'Only allowed : doc, docx, png, jpeg, jpg, pdf, svg, mp4, gif, webp, csv'), config('httpcodes.internal_server_error'));
                }

                //********************************
                //scan all files
                $finfo = finfo_open(FILEINFO_MIME_TYPE); // Return MIME type
                $fileActCheck = finfo_file($finfo, $file);
                finfo_close($finfo);
                if($fileActCheck=='application/x-dosexec' || $fileActCheck=='application/x-msdownload')
                {
                    return response()->json(prepareResult(true, [], trans('translate.malicious_file').'Blocked for security reasons! '), config('httpcodes.internal_server_error'));
                }
                //********************************

                $fileName   = time().'-'.rand(1,999999) . '.' . $extension;
                $filePath = 'uploads/' . $fileName;
                $file->storeAs('public/uploads',$fileName);
                if(env('APP_ENV', 'local')==='production')
                {
                    $callApi = secure_url('api/file-access/'.$filePath);
                }
                else
                {
                    $callApi = url('api/file-access/'.$filePath);
                }
                
                $fileInfo = [
                    'file_name'         => $callApi,
                    'file_extension'    => $extension,
                    'uploading_file_name' => $file->getClientOriginalName(),
                ];
                return response(prepareResult(false, $fileInfo, trans('translate.created')),config('httpcodes.created'));
            }   
        }
        catch (\Throwable $e) {
            \Log::error($e);
            return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }


    public function getFile($folderName,$fileName)
    {
        $path = storage_path('app/public/'.$folderName.'/'. $fileName);

        // return $path;

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }
}
