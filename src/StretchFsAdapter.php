<?php
namespace MeisamMulla\FlysystemStretchfs;

use Exception;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use MeisamMulla\SfsClient\StretchFS;

class StretchFsAdapter implements FilesystemAdapter
{
    protected $client;

    public function __construct(StretchFS $client)
    {
        $this->client = $client;
    }

    public function fileExists(string $path): bool
    {
        try {
            $this->client->fileDetail($path);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $item = $this->client->fileDetail($path);

            return $item['folder'];
        } catch (Exception $e) {
            return false;
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client->fileUploadFromString(filePath: $path, contents: $contents);
        } catch (Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $resource, Config $config): void
    {
        if (is_resource($resource)) {
            $contents = stream_get_contents($resource);
            fclose($resource);

            $this->write($path, $contents, $config);
        } else {
            throw UnableToWriteFile::atLocation($path, 'The provided resource is not valid.');
        }
    }

    public function read(string $path): string
    {
        try {
            return $this->client->fileDownload($path);
        } catch (Exception $e) {
            throw new UnableToReadFile($path, $e->getMessage());
        }
    }

    public function readStream(string $path)
    {
        try {
            $stream = $this->client->fileDownloadStream($path);

            return ['stream' => $stream];
        } catch (Exception $e) {
            throw new UnableToReadFile($path, $e->getMessage(), $e);
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->client->fileDelete($path);
        } catch (Exception $e) {
            throw new UnableToDeleteFile($path, $e->getMessage());
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $this->client->folderDelete($path);
        } catch (Exception $e) {
            throw new UnableToDeleteDirectory($path, $e->getMessage());
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->folderCreate($path);
        } catch (Exception $e) {
            throw new UnableToCreateDirectory($path, $e->getMessage());
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Adapter does not support visibility controls.');
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $item = $this->client->fileDetail($path);
            return FileAttributes::fromArray([
                'path' => $path,
                'mimetype' => $item['mimetype'],
            ]);
        } catch (Exception $e) {
            throw new UnableToRetrieveMetadata($path, $e->getMessage());
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $item = $this->client->fileDetail($path);

            return FileAttributes::fromArray([
                'path' => $path,
                'timestamp' => $item['file']['updatedAt'],
            ]);
        } catch (Exception $e) {
            throw new UnableToRetrieveMetadata($path, $e->getMessage());
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $item = $this->client->fileDetail($path);

            return FileAttributes::fromArray([
                'path' => $path,
                'fileSize' => $item['file']['size'],
            ]);
        } catch (Exception $e) {
            throw new UnableToRetrieveMetadata($path, $e->getMessage());
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        try {
            $items = $this->client->fileList($path);

            foreach ($items as $item) {
                yield FileAttributes::fromArray([
                    'path' => $item['path'],
                    'type' => $item['folder'] ? 'dir' : 'file',
                    'visibility' => 'private',
                    'size' => $item['folder'] ? 0 : $item['file']['size'],
                    'mimetype' => $item['folder'] ? 'directory' : $item['mimetype'],
                    'timestamp' => $item['folder'] ? null : $item['file']['updatedAt'],
                ]);
            }
        } catch (Exception $e) {
            throw new FilesystemException($path, $e->getMessage());
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {

    }

    public function copy(string $source, string $destination, Config $config): void
    {

    }

}
