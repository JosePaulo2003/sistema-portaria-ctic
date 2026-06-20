<section class="section-header">
    <h1>Meu Perfil</h1>
    <p><?= e($user['perfil_nome'] ?? '') ?></p>
</section>
<form method="post" enctype="multipart/form-data" class="card form-grid">
    <?= csrfField() ?>
    <div class="profile-photo-field">
        <img
            id="profile-photo-preview"
            class="profile-photo-preview"
            src="<?= e(!empty($user['foto_perfil_url']) ? baseUrl($user['foto_perfil_url']) : assetUrl('assets/uea_logo1.png')) ?>"
            alt="Foto de perfil"
        >
        <label>Foto de perfil
            <input type="file" name="foto" accept="image/png,image/jpeg,image/webp" data-preview="#profile-photo-preview">
        </label>
        <small>JPG, PNG ou WEBP até 2 MB.</small>
    </div>
    <label>Nome
        <input name="nome" required value="<?= e($user['nome'] ?? '') ?>">
    </label>
    <label>E-mail
        <input value="<?= e($user['email'] ?? '') ?>" disabled>
    </label>
    <label>Nova senha
        <input type="password" name="senha" minlength="8" placeholder="Deixe em branco para manter">
    </label>
    <div class="form-actions">
        <button class="button" type="submit">Salvar</button>
    </div>
</form>
