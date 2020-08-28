<?php

namespace Bavix\Flysystem\Glow;

use Bavix\GlowApi\Api;
use Bavix\GlowApi\File\Upload;
use Bavix\GlowApi\HttpClient;
use Carbon\Carbon;
use League\Flysystem\Config;
use League\Flysystem\AdapterInterface;

class GlowAdapter implements AdapterInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Api
     */
    protected $glow;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->glow = new Api(new HttpClient(
            $config->get('endpoint'),
            $config->get('token'),
        ));
    }

    /**
     * @param string $path
     * @return string
     */
    public function getUrl(string $path): string
    {
        $prefix = $this->isOpened($this->config) ? '' : '_';
        $url = \rtrim($this->config->get('url'), '/');
        $urn = '/' . $prefix . $this->config->get('bucket') . '/' . \ltrim($path, '/');
        return $url . $urn;
    }

    /**
     * @param string $path
     * @param $expiration
     * @param array $options
     * @return string
     */
    public function getTemporaryUrl(string $path, $expiration, array $options): string
    {
        $invite = $this->glow->inviteFile(
            $this->config->get('bucket'),
            $path,
            Carbon::parse($expiration)->toDateTime(),
            $options
        );

        return $invite['uri'];
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, Config $config)
    {
        return $this->writeStream(
            $path,
            fopen('data://text/plain,' . $contents, 'rb'),
            $config,
        );
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        $file = (new Upload())->addFile(
            $path,
            $resource,
            $this->isOpened($config),
        );

        return $this->glow->writeFile(
            $config->setFallback($this->config)->get('bucket'),
            $file,
        );
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, Config $config)
    {
        return $this->updateStream(
            $path,
            fopen('data://text/plain,' . $contents, 'rb'),
            $config,
        );
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, Config $config)
    {
        $file = (new Upload())->addFile(
            $path,
            $resource,
            $this->isOpened($config),
        );

        return $this->glow->rewriteFile(
            $config->setFallback($this->config)->get('bucket'),
            $file,
        );
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newpath)
    {
        try {
            return $this->copy($path, $newpath) && $this->delete($path);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function copy($path, $newpath)
    {
        try {
            return (bool)$this->writeStream(
                $newpath,
                $this->readStream($path)['stream'],
                $this->config
            );
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($path)
    {
        return $this->glow->dropFile(
            $this->config->get('bucket'),
            $path,
        );
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        try {
            $this->glow->dropBucket($dirname);
            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, Config $config)
    {
        try {
            $this->glow->createBucket($dirname);
            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        return $this->glow->visibilityFile(
            $this->config->get('bucket'),
            $path,
            $visibility === AdapterInterface::VISIBILITY_PUBLIC,
        );
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        try {
            return (bool)$this->glow->showFile(
                $this->config->get('bucket'),
                $path,
            );
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        try {
            $contents = \stream_get_contents(
                $this->readStream($path)['stream']
            );

            return \compact('contents');
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        try {
            $info = $this->glow->showFile(
                $this->config->get('bucket'),
                $path,
            );

            $stream = \fopen($info['uri'], 'rb');
            return \compact('stream');
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function listContents($directory = '', $recursive = false)
    {
        $uniqDirs = [];
        $results = [];
        $bucket = $this->config->get('bucket');
        $iterate = $this->glow->allFiles($bucket);
        foreach ($iterate as $file) {
            $path = \substr($file['route'], \strlen($bucket) + 1);

            $paths = ['.'];
            $dirPath = \dirname($path);
            if ($dirPath !== '.') {
                $paths = \explode('/', $dirPath);
            }

            $dirname = $directory === '' ? current($paths) : $directory;
            if (!$recursive && count($paths) > 1) {
                $paths = [\current($paths), \end($paths)];
            }

            if (empty($file['extra']) || !\in_array($dirname, $paths, true)) {
                continue;
            }

            $results[] = \compact('path') + $file['extra'];

            $breadcrumbs = [];
            foreach ($paths as $dir) {
                if ($dir === '.') {
                    continue;
                }

                $breadcrumbs[] = $dir;
                $folderPath = \implode('/', $breadcrumbs);
                if (!isset($uniqDirs[$folderPath])) {
                    $uniqDirs[$folderPath] = true;
                    $results[] = [
                        'type' => 'dir',
                        'path' => \implode('/', $breadcrumbs),
                        'timestamp' => $file['extra']['timestamp'],
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        $metadata = $this->glow->showFile(
            $this->config->get('bucket'),
            $path,
        );

        return \array_merge($metadata['extra'] ?? [], [
            'visibility' => $metadata['visibility'] ?? false,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @param Config $config
     * @return bool
     */
    protected function isOpened(Config $config): bool
    {
        $visibility = (clone $config)
            ->setFallback($this->config)
            ->get('visibility', AdapterInterface::VISIBILITY_PUBLIC);

        return $visibility === AdapterInterface::VISIBILITY_PUBLIC;
    }

}
