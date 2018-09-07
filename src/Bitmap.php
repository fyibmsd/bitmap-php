<?php

namespace Bitmap;

use InvalidArgumentException;

class Bitmap
{
    protected $handle = null;

    protected $max = 0;

    protected $filename = null;

    const MIN_OFFSET = 0;

    const MAX_OFFSET = (1 << 32) - 1;

    /**
     * Bitmap constructor.
     * @param array $config path / filename configuration
     */
    public function __construct(array $config)
    {
        $config = array_merge([
            'path'     => '/tmp/',
            'filename' => 'bitm.tmp'
        ], $config);

        $this->filename = $config['path'] . $config['filename'];

        clearstatcache(true, $this->filename);

        if (file_exists($this->filename))
            $this->handle = fopen($this->filename, 'r+');
        else
            $this->handle = fopen($this->filename, 'w+');

        $this->max = filesize($this->filename) * 8;
    }

    /**
     * set value by offset
     * @param $offset
     * @param $value
     */
    public function setbit($offset, $value)
    {
        if (!$this->numcheck($offset))
            throw new InvalidArgumentException('Offset is not valid!');

        if ($value !== 0 && $value !== 1)
            throw new InvalidArgumentException('Bitmap value should be 0 or 1!');

        fseek($this->handle, floor($offset / 8), SEEK_SET);

        $bin = $value === 1 ?
            fread($this->handle, 1) | pack('C', 0x100 >> fmod($offset, 8) + 1) :
            fread($this->handle, 1) & ~pack('C', 0x100 >> fmod($offset, 8) + 1);

        fseek($this->handle, ftell($this->handle) - 1, SEEK_SET);
        fwrite($this->handle, $bin);
        fflush($this->handle);
    }

    /**
     * get value by offset
     * @param $offset
     * @return bool|int
     */
    public function getbit($offset)
    {
        if (!$this->numcheck($offset))
            throw new InvalidArgumentException('Offset is not valid!');

        if (fseek($this->handle, floor($offset / 8), SEEK_SET) == -1)
            return false;

        $bin = fread($this->handle, 1);

        $item = $bin & pack('C', 0x100 >> fmod($offset, 8) + 1);

        return $item === "\x00" ? 0 : 1;
    }

    /**
     * get bitmap count (bytes * 8)
     * @return int
     */
    public function bitcount()
    {
        return $this->max;
    }

    protected function numcheck($num)
    {
        if ($num > $this->max) {
            fseek($this->handle, 0, SEEK_END);
            fwrite($this->handle, str_repeat("\x00", ceil(($num - $this->max) / 8)));
            $this->max = ceil($num / 8) * 8;
        }

        return $num >= self::MIN_OFFSET && $num <= self::MAX_OFFSET;
    }

    /**
     * destroy the bitmap
     * @return bool
     */
    public function destroy()
    {
        return unlink($this->filename);
    }

    public function __destruct()
    {
        fclose($this->handle);
    }
}
