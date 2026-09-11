<aside
    class="withdrawal-alert-center"
    data-withdrawal-alert-center
    data-list-url="<?= e(baseUrl('/portaria/retiradas/pendentes')) ?>"
    aria-live="polite"
    hidden
>
    <header class="withdrawal-alert-center__header">
        <div>
            <span class="withdrawal-alert-center__eyebrow">Confirmação presencial</span>
            <strong><span data-withdrawal-alert-count>0</span> aguardando confirmação</strong>
            <small>Somente Alunos, Bolsistas e Estagiários</small>
        </div>
        <button class="withdrawal-alert-center__collapse" type="button" data-withdrawal-center-toggle aria-expanded="true">Minimizar painel</button>
    </header>
    <label class="withdrawal-alert-center__filter">
        Perfil com solicitação
        <select data-withdrawal-group-filter>
            <option value="">Todos os grupos</option>
        </select>
    </label>
    <div class="withdrawal-alert-center__items" data-withdrawal-alert-items></div>
</aside>
