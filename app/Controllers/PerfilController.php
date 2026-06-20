<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

// Permite ao usuário atualizar dados básicos, senha e foto de perfil.
class PerfilController extends Controller
{
    public function edit(): void
    {
        requireAuth();
        $this->view('perfil/edit', ['title' => 'Meu Perfil', 'user' => currentUser()]);
    }

    public function update(): void
    {
        requireAuth();
        verifyCsrf();
        $data = ['nome' => trim((string) ($_POST['nome'] ?? ''))];
        if (!empty($_POST['senha'])) {
            $data['senha_hash'] = password_hash((string) $_POST['senha'], PASSWORD_DEFAULT);
        }

        if (!empty($_FILES['foto']['tmp_name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $file = $_FILES['foto'];
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
                flash('error', 'Não foi possível enviar a foto. Use imagem até 2 MB.');
                redirect('/perfil');
            }
            $info = @getimagesize($file['tmp_name']);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!$info || empty($allowed[$info['mime'] ?? '']) || empty($allowed[$mime]) || $mime !== ($info['mime'] ?? '')) {
                flash('error', 'Envie uma imagem JPG, PNG ou WEBP.');
                redirect('/perfil');
            }
            if (($info[0] ?? 0) > 3000 || ($info[1] ?? 0) > 3000) {
                flash('error', 'A foto deve ter no máximo 3000px de largura ou altura.');
                redirect('/perfil');
            }
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/perfis';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $filename = 'perfil_' . currentUser()['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$info['mime']];
            $destination = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                flash('error', 'Não foi possível salvar a foto.');
                redirect('/perfil');
            }
            chmod($destination, 0644);
            $data['foto_perfil_url'] = 'public/uploads/perfis/' . $filename;
        }

        (new User())->update((int) currentUser()['id'], $data);
        unset($_SESSION['_user_cache']);
        audit('Perfil', 'edicao', 'Perfil atualizado.');
        flash('success', 'Perfil atualizado com sucesso.');
        redirect('/perfil');
    }
}
