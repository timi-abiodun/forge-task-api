<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attachment Storage Disk
    |--------------------------------------------------------------------------
    |
    | Attachments should resolve their storage disk once at upload time and
    | persist that value on the attachment row. This keeps the upload path
    | configurable without introducing a separate storage abstraction layer.
    |
    */

    'disk' => env('ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'local')),

];