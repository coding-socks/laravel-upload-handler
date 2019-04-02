<?php


namespace LaraCrafts\ChunkUploader\Helper;


use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Range\Range;
use LaraCrafts\ChunkUploader\StorageConfig;

trait ChunkHelpers
{
    /**
     * @var int
     */
    protected $bufferSize = 4096;

    /**
     * @param StorageConfig $config
     * @param array $chunks
     * @param string $targetFilename
     *
     * @return string
     */
    public function mergeChunks(StorageConfig $config, array $chunks, string $targetFilename): string
    {
        $disk = Storage::disk($config->getDisk());

        $mergedDirectory = $config->getMergedDirectory();
        $disk->makeDirectory($mergedDirectory);
        $targetPath = $mergedDirectory . '/' . $targetFilename;

        $chunk = array_shift($chunks);
        $disk->copy($chunk, $targetPath);
        $mergedFile = new File($disk->path($targetPath));
        $mergedFileInfo = $mergedFile->openFile('ab+');

        foreach ($chunks as $chunk) {
            $chunkFileInfo = (new File($disk->path($chunk)))->openFile('rb');

            while (! $chunkFileInfo->eof()) {
                $mergedFileInfo->fwrite($chunkFileInfo->fread($this->bufferSize));
            }
        }

        return $targetPath;
    }

    /**
     * @param StorageConfig $config
     * @param Range $range
     * @param UploadedFile $file
     * @param string $uuid
     * @return array
     */
    public function storeChunk(StorageConfig $config, Range $range, UploadedFile $file, string $uuid)
    {
        $len = strlen($range->getTotal());
        $chunkname = implode('-', [
            str_pad($range->getStart(), $len, '0', STR_PAD_LEFT),
            str_pad($range->getEnd(), $len, '0', STR_PAD_LEFT),
        ]);

        $directory = $config->getChunkDirectory() . '/' . $uuid;
        $file->storeAs($directory, $chunkname, [
            'disk' => $config->getDisk(),
        ]);

        return Storage::disk($config->getDisk())->files($directory);
    }
}