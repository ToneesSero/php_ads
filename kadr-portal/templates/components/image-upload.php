<?php

declare(strict_types=1);

/**
 * @var array<int, array{id:string,path:string,thumb:string}> $uploadedImages
 * @var int $uploadLimit
 * @var int $uploadMaxSize
 */

$uploadSizeMb = number_format($uploadMaxSize / 1024 / 1024, 1, ',', ' ');
?>
<div class="image-upload" data-image-upload data-upload-url="/api/upload" data-delete-url="/api/upload/delete" data-max-images="<?= htmlspecialchars((string) $uploadLimit, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="image-upload__dropzone" data-upload-dropzone>
        <p>Перетащите изображения сюда или нажмите, чтобы выбрать файлы.</p>
        <button type="button" class="button button-secondary" data-upload-trigger>Выбрать файлы</button>
        <p class="image-upload__hint">Допустимые форматы: JPG, PNG. Максимальный размер файла — <?= htmlspecialchars($uploadSizeMb, ENT_QUOTES, 'UTF-8'); ?> МБ.</p>
    </div>
    <input type="file" accept="image/jpeg,image/png" multiple hidden data-upload-input>
    <div class="image-upload__preview" data-upload-list>
        <?php foreach ($uploadedImages as $image) : ?>
            <div class="image-upload__item" data-upload-item data-upload-id="<?= htmlspecialchars($image['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?= htmlspecialchars($image['thumb'], ENT_QUOTES, 'UTF-8'); ?>" alt="Загруженное изображение">
                <button type="button" class="image-upload__remove" data-upload-remove aria-label="Удалить изображение">&times;</button>
                <input type="hidden" name="uploaded_images[]" value="<?= htmlspecialchars($image['id'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        <?php endforeach; ?>
    </div>
</div>
