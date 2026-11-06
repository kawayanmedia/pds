<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Renderer;

enum OutputMode: string
{
    case String = 'S';

    case File = 'F';

    case Inline = 'I';

    case Download = 'D';
}
