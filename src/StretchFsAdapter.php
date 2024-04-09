<?php
namespace MeisamMulla\FlysystemStretchfs;

use Exception;
use DateTimeInterface;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use MeisamMulla\SfsClient\StretchFS;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToRetrieveMetadata;
use League\MimeTypeDetection\MimeTypeDetector;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;

class StretchFsAdapter implements FilesystemAdapter, TemporaryUrlGenerator, PublicUrlGenerator
{
    protected $client;
    protected MimeTypeDetector $mimeTypeDetector;

    public function __construct(StretchFS $client, MimeTypeDetector $mimeTypeDetector = null)
    {
        $this->client = $client;
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
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
            $this->client->fileUploadFromResource($resource, basename($path), pathinfo($path, PATHINFO_DIRNAME));
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

            return new FileAttributes(
                $path,
                null,
                null,
                null,
                $this->mimeTypeDetector->detectMimeTypeFromPath($path)
            );
        } catch (Exception $e) {
            throw new UnableToRetrieveMetadata($path, $e->getMessage());
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $item = $this->client->fileDetail($path);

            return new FileAttributes(
                $path,
                null,
                null,
                strtotime($item['file']['updatedAt'])
            );
        } catch (Exception $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage());
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $item = $this->client->fileDetail($path);

            return new FileAttributes(
                $path,
                $item['file']['size'] ?? null
            );
        } catch (Exception $e) {
            throw new UnableToRetrieveMetadata($path, $e->getMessage());
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        try {
            $items = $this->client->fileList($path);

            foreach ($items['files'] as $item) {
                yield FileAttributes::fromArray([
                    'path' => $item['name'],
                    'type' => $item['folder'] ? 'dir' : 'file',
                    'visibility' => 'private',
                    'size' => $item['size'],
                    'mimetype' => $this->mimeTypeDetector->detectMimeTypeFromPath($item['path']),
                    'timestamp' => strtotime($item['updatedAt']),
                ]);
            }
        } catch (Exception $e) {
            throw new FilesystemException($path, $e->getMessage());
        }
    }

    public function getTemporaryUrl(string $path, DateTimeInterface $expiration, $config): string
    {
        try {
            return $this->client->fileDownloadUrl($path, $expiration->getTimestamp() - time())['url'];
        } catch (Exception $e) {
            throw new UnableToGenerateTemporaryUrl($path, $e->getMessage());
        }

    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, $config): string
    {
        return $this->getTemporaryUrl($path, $expiresAt, $config);
    }

    public function getUrl(string $path): string
    {
        return $this->client->fileDownloadUrl($path)['url'];
    }

    public function publicUrl(string $path, $config): string
    {
        return $this->client->fileDownloadUrl($path)['url'];
    }

    public function move(string $source, string $destination, Config $config): void
    {

    }

    public function copy(string $source, string $destination, Config $config): void
    {

    }

}
