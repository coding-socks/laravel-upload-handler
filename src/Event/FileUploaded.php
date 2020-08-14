<?php

namespace CodingSocks\UploadHandler\Event;

class FileUploaded
{
    /**
     * @var string
     */
    public $disk;

    /**
     * @var string
     */
    public $file;

    /**
     * FileUploaded constructor.
     *
     * @param string $disk
     * @param string $file
     */
    public function __construct(string $disk, string $file)
    {
        $this->disk = $disk;
        $this->file = $file;
    }
}
