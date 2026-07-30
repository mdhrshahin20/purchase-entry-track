<?php

namespace App\Foundation;

/**
 * View renderer with optional layout (Blade-style composition, plain PHP).
 */
class View
{
    public function __construct(private string $path)
    {
    }

    public function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = $this->renderFile($view, $data);

        if ($layout === null) {
            return $content;
        }

        return $this->renderFile($layout, array_merge($data, ['content' => $content]));
    }

    private function renderFile(string $view, array $data): string
    {
        $file = $this->path . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View [{$view}] not found.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
