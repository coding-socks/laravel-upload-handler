<?php

namespace LaraCrafts\ChunkUploader\Helper;

use Illuminate\Contracts\Filesystem\Filesystem;
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
     * Combine all the given chunks to one single file with the given filename.
     *
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     * @param array $chunks
     * @param string $targetFilename
     *
     * @return string The path of the created file.
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

        return $this->correctMergedExt($disk, $mergedDirectory, $targetFilename);
    }

    /**
     * Delete a directory with the given name from the chunk directory.
     *
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     * @param string $uuid
     */
    public function deleteChunkDirectory(StorageConfig $config, string $uuid): void
    {
        $directory = $config->getChunkDirectory() . '/' . $uuid;
        Storage::disk($config->getDisk())->deleteDirectory($directory);
    }

    /**
     * Persist an uploaded chunk in a directory with the given name in the chunk directory.
     *
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     * @param \LaraCrafts\ChunkUploader\Range\Range $range
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $uuid
     *
     * @return array
     */
    public function storeChunk(StorageConfig $config, Range $range, UploadedFile $file, string $uuid): array
    {
        $chunkname = $this->buildChunkname($range);

        $directory = $config->getChunkDirectory() . '/' . $uuid;
        $file->storeAs($directory, $chunkname, [
            'disk' => $config->getDisk(),
        ]);

        return Storage::disk($config->getDisk())->files($directory);
    }

    /**
     * List all chunks from a directory with the given name.
     *
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     * @param string $uuid
     *
     * @return array
     */
    public function chunks(StorageConfig $config, string $uuid): array
    {
        $directory = $config->getChunkDirectory() . '/' . $uuid;
        return Storage::disk($config->getDisk())->files($directory);
    }

    /**
     * Create a chunkname which contains range details.
     *
     * @param \LaraCrafts\ChunkUploader\Range\Range $range
     *
     * @return string
     */
    public function buildChunkname(Range $range): string
    {
        $len = strlen($range->getTotal());
        return implode('-', [
            str_pad($range->getStart(), $len, '0', STR_PAD_LEFT),
            str_pad($range->getEnd(), $len, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Check if a chunk exists.
     *
     * When chunkname is given it checks the exact chunk. Otherwise only the folder has to exists.
     *
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     * @param string $uuid
     * @param string|null $chunkname
     *
     * @return bool
     */
    public function chunkExists(StorageConfig $config, string $uuid, string $chunkname = null): bool
    {
        $directory = $config->getChunkDirectory() . '/' . $uuid;
        $disk = Storage::disk($config->getDisk());

        if (!$disk->exists($directory)) {
            return false;
        }

        return $chunkname === null || $disk->exists($directory . '/' . $chunkname);
    }

    /**
     * @param \Illuminate\Contracts\Filesystem\Filesystem $disk
     * @param string $mergedDirectory
     * @param string $targetFilename
     *
     * @return string
     */
    private function correctMergedExt(Filesystem $disk, string $mergedDirectory, string $targetFilename): string
    {
        $targetPath = $mergedDirectory . '/' . $targetFilename;
        $ext = pathinfo($targetFilename, PATHINFO_EXTENSION);
        if ($ext === 'bin') {
            $var = $disk->path($targetPath);
            $uploadedFile = new UploadedFile($var, $targetFilename);
            $filename = pathinfo($targetFilename, PATHINFO_FILENAME);
            $fixedTargetPath = $mergedDirectory . '/' . $filename . '.' . $uploadedFile->guessExtension();
            $disk->move($targetPath, $fixedTargetPath);
            $targetPath = $fixedTargetPath;
        }
        return $targetPath;
    }
}
