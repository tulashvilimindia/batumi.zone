<?php
/**
 * Template Name: Profile
 * Description: User profile management page
 *
 * @package Batumi_Theme
 * @since 0.3.0
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';
$current_user = wp_get_current_user();

// Multilingual labels
$labels = array(
    'ge' => array(
        'title' => 'პროფილის მართვა',
        'subtitle' => 'განაახლეთ თქვენი პროფილის ინფორმაცია',
        'personal_info' => 'პირადი ინფორმაცია',
        'username' => 'მომხმარებლის სახელი',
        'email' => 'ელ. ფოსტა',
        'phone' => 'ტელეფონი',
        'phone_required' => 'სავალდებულოა (საჭიროა გამოქვეყნებისთვის)',
        'whatsapp' => 'WhatsApp',
        'member_since' => 'წევრი',
        'account_stats' => 'ანგარიშის სტატისტიკა',
        'total_listings' => 'სულ განცხადება',
        'published' => 'გამოქვეყნებული',
        'draft' => 'დაუმთავრებელი',
        'paused' => 'დაპაუზებული',
        'change_password' => 'პაროლის შეცვლა',
        'current_password' => 'მიმდინარე პაროლი',
        'new_password' => 'ახალი პაროლი',
        'confirm_password' => 'გაიმეორეთ პაროლი',
        'save_changes' => 'ცვლილებების შენახვა',
        'cancel' => 'გაუქმება',
        'delete_account' => 'ანგარიშის წაშლა',
        'delete_warning' => 'ამ ქმედების გაუქმება შეუძლებელია. ყველა თქვენი განცხადება წაიშლება.',
        'delete_btn' => 'დადასტურება და წაშლა'
    ),
    'ru' => array(
        'title' => 'Управление профилем',
        'subtitle' => 'Обновите информацию вашего профиля',
        'personal_info' => 'Личная информация',
        'username' => 'Имя пользователя',
        'email' => 'Эл. почта',
        'phone' => 'Телефон',
        'phone_required' => 'Обязательно (требуется для публикации)',
        'whatsapp' => 'WhatsApp',
        'member_since' => 'Участник с',
        'account_stats' => 'Статистика аккаунта',
        'total_listings' => 'Всего объявлений',
        'published' => 'Опубликовано',
        'draft' => 'Черновик',
        'paused' => 'Приостановлено',
        'change_password' => 'Изменить пароль',
        'current_password' => 'Текущий пароль',
        'new_password' => 'Новый пароль',
        'confirm_password' => 'Повторите пароль',
        'save_changes' => 'Сохранить изменения',
        'cancel' => 'Отмена',
        'delete_account' => 'Удалить аккаунт',
        'delete_warning' => 'Это действие нельзя отменить. Все ваши объявления будут удалены.',
        'delete_btn' => 'Подтвердить и удалить'
    ),
    'en' => array(
        'title' => 'Profile Management',
        'subtitle' => 'Update your profile information',
        'personal_info' => 'Personal Information',
        'username' => 'Username',
        'email' => 'Email',
        'phone' => 'Phone',
        'phone_required' => 'Required (needed for publishing)',
        'whatsapp' => 'WhatsApp',
        'member_since' => 'Member since',
        'account_stats' => 'Account Statistics',
        'total_listings' => 'Total Listings',
        'published' => 'Published',
        'draft' => 'Draft',
        'paused' => 'Paused',
        'change_password' => 'Change Password',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm Password',
        'save_changes' => 'Save Changes',
        'cancel' => 'Cancel',
        'delete_account' => 'Delete Account',
        'delete_warning' => 'This action cannot be undone. All your listings will be deleted.',
        'delete_btn' => 'Confirm and Delete'
    )
);

$l = $labels[$current_lang] ?? $labels['ge'];
?>

<main id="primary" class="site-main profile-page">
    <div class="container profile-container">

        <!-- Page Header -->
        <header class="profile-header">
            <h1><?php echo esc_html($l['title']); ?></h1>
            <p><?php echo esc_html($l['subtitle']); ?></p>
        </header>

        <!-- Messages -->
        <div id="profile-messages"></div>

        <!-- Account Stats Card -->
        <div class="profile-card">
            <div class="profile-card-header">
                <h2><?php echo esc_html($l['account_stats']); ?></h2>
            </div>
            <div class="account-stats" id="account-stats">
                <div class="stat-item">
                    <span class="stat-value" id="stat-total">-</span>
                    <span class="stat-label"><?php echo esc_html($l['total_listings']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="stat-published">-</span>
                    <span class="stat-label"><?php echo esc_html($l['published']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="stat-draft">-</span>
                    <span class="stat-label"><?php echo esc_html($l['draft']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="stat-paused">-</span>
                    <span class="stat-label"><?php echo esc_html($l['paused']); ?></span>
                </div>
            </div>
        </div>

        <!-- Personal Information Form -->
        <form id="profile-form" class="profile-card">
            <div class="profile-card-header">
                <h2><?php echo esc_html($l['personal_info']); ?></h2>
            </div>

            <div class="form-group">
                <label class="form-label"><?php echo esc_html($l['username']); ?></label>
                <input
                    type="text"
                    id="username"
                    class="form-input"
                    value="<?php echo esc_attr($current_user->user_login); ?>"
                    disabled
                    readonly>
                <small class="form-help">Username cannot be changed</small>
            </div>

            <div class="form-group">
                <label class="form-label"><?php echo esc_html($l['email']); ?></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    value="<?php echo esc_attr($current_user->user_email); ?>"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <?php echo esc_html($l['phone']); ?> *
                    <span class="required-badge"><?php echo esc_html($l['phone_required']); ?></span>
                </label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="form-input"
                    value="<?php echo esc_attr(get_user_meta($current_user->ID, 'phone', true)); ?>"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label"><?php echo esc_html($l['whatsapp']); ?></label>
                <input
                    type="tel"
                    id="whatsapp"
                    name="whatsapp"
                    class="form-input"
                    value="<?php echo esc_attr(get_user_meta($current_user->ID, 'whatsapp', true)); ?>">
            </div>

            <div class="form-group">
                <label class="form-label"><?php echo esc_html($l['member_since']); ?></label>
                <input
                    type="text"
                    class="form-input"
                    value="<?php echo esc_attr(date('F Y', strtotime($current_user->user_registered))); ?>"
                    disabled
                    readonly>
            </div>

            <!-- Password Change Section -->
            <div class="password-section">
                <button type="button" id="password-toggle-btn" class="password-toggle-btn">
                    <?php echo esc_html($l['change_password']); ?> →
                </button>

                <div id="password-fields" class="password-fields">
                    <div class="form-group">
                        <label class="form-label"><?php echo esc_html($l['current_password']); ?></label>
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="form-input"
                            autocomplete="current-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo esc_html($l['new_password']); ?></label>
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="form-input"
                            autocomplete="new-password"
                            minlength="8">
                        <small class="form-help">Minimum 8 characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo esc_html($l['confirm_password']); ?></label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-input"
                            autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" id="save-btn" class="btn btn-primary">
                    <?php echo esc_html($l['save_changes']); ?>
                </button>
            </div>
        </form>

        <!-- Delete Account Card -->
        <div class="profile-card danger-zone">
            <h3><?php echo esc_html($l['delete_account']); ?></h3>
            <p><?php echo esc_html($l['delete_warning']); ?></p>
            <button type="button" id="delete-account-btn" class="btn-danger-outline">
                <?php echo esc_html($l['delete_btn']); ?>
            </button>
        </div>

    </div>
</main>

<script>
/**
 * Profile Page JavaScript
 * Handles profile updates, password changes, account deletion
 */
(function() {
    'use strict';

    const apiBase = '<?php echo esc_url(rest_url('batumizone/v1')); ?>';
    const wpNonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
    const currentLang = '<?php echo esc_js($current_lang); ?>';

    const ProfilePage = {
        init() {
            this.loadAccountStats();
            this.bindEvents();
        },

        bindEvents() {
            document.getElementById('profile-form').addEventListener('submit', (e) => this.handleSubmit(e));
            document.getElementById('password-toggle-btn').addEventListener('click', () => this.togglePasswordFields());
            document.getElementById('delete-account-btn').addEventListener('click', () => this.confirmDeleteAccount());
        },

        async loadAccountStats() {
            try {
                const response = await fetch(`${apiBase}/my/services`, {
                    headers: {
                        'X-WP-Nonce': wpNonce
                    }
                });

                if (!response.ok) throw new Error('Failed to load stats');

                const services = await response.json();

                const stats = {
                    total: services.length,
                    published: services.filter(s => s.status === 'publish').length,
                    draft: services.filter(s => s.status === 'draft').length,
                    paused: services.filter(s => s.status === 'inactive').length
                };

                document.getElementById('stat-total').textContent = stats.total;
                document.getElementById('stat-published').textContent = stats.published;
                document.getElementById('stat-draft').textContent = stats.draft;
                document.getElementById('stat-paused').textContent = stats.paused;

            } catch (e) {
                console.error('Error loading stats:', e);
            }
        },

        togglePasswordFields() {
            const fields = document.getElementById('password-fields');
            fields.classList.toggle('active');
        },

        async handleSubmit(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('save-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = currentLang === 'ge' ? 'ინახება...' : currentLang === 'ru' ? 'Сохранение...' : 'Saving...';

            const formData = {
                email: document.getElementById('email').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                whatsapp: document.getElementById('whatsapp').value.trim()
            };

            // Add password if changing
            const newPassword = document.getElementById('new_password').value;
            if (newPassword) {
                const confirmPassword = document.getElementById('confirm_password').value;
                if (newPassword !== confirmPassword) {
                    this.showMessage('error', 'Passwords do not match');
                    submitBtn.disabled = false;
                    submitBtn.textContent = currentLang === 'ge' ? 'ცვლილებების შენახვა' : currentLang === 'ru' ? 'Сохранить изменения' : 'Save Changes';
                    return;
                }
                formData.password = newPassword;
            }

            try {
                const response = await fetch(`${apiBase}/me`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpNonce
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Failed to update profile');
                }

                this.showMessage('success', currentLang === 'ge' ? 'პროფილი განახლდა!' : currentLang === 'ru' ? 'Профиль обновлен!' : 'Profile updated!');

                // Clear password fields
                document.getElementById('current_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_password').value = '';
                document.getElementById('password-fields').classList.remove('active');

            } catch (e) {
                this.showMessage('error', e.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = currentLang === 'ge' ? 'ცვლილებების შენახვა' : currentLang === 'ru' ? 'Сохранить изменения' : 'Save Changes';
            }
        },

        confirmDeleteAccount() {
            const confirmMsg = currentLang === 'ge'
                ? 'დარწმუნებული ხართ რომ გსურთ ანგარიშის წაშლა? ეს ქმედება შეუქცევადია!'
                : currentLang === 'ru'
                ? 'Вы уверены, что хотите удалить аккаунт? Это действие необратимо!'
                : 'Are you sure you want to delete your account? This action cannot be undone!';

            if (confirm(confirmMsg)) {
                const secondConfirm = currentLang === 'ge'
                    ? 'ბოლო გაფრთხილება: ყველა თქვენი განცხადება წაიშლება. გაგრძელდეს?'
                    : currentLang === 'ru'
                    ? 'Последнее предупреждение: все ваши объявления будут удалены. Продолжить?'
                    : 'Final warning: all your listings will be deleted. Continue?';

                if (confirm(secondConfirm)) {
                    this.deleteAccount();
                }
            }
        },

        async deleteAccount() {
            try {
                const response = await fetch(`${apiBase}/me`, {
                    method: 'DELETE',
                    headers: {
                        'X-WP-Nonce': wpNonce
                    }
                });

                if (!response.ok) throw new Error('Failed to delete account');

                alert(currentLang === 'ge' ? 'ანგარიში წაიშალა' : currentLang === 'ru' ? 'Аккаунт удален' : 'Account deleted');
                window.location.href = '/';

            } catch (e) {
                this.showMessage('error', e.message);
            }
        },

        showMessage(type, message) {
            const messagesDiv = document.getElementById('profile-messages');
            messagesDiv.innerHTML = `
                <div class="message message-${type}">
                    ${message}
                </div>
            `;

            setTimeout(() => {
                messagesDiv.innerHTML = '';
            }, 5000);

            // Scroll to messages
            messagesDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    // Initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => ProfilePage.init());
    } else {
        ProfilePage.init();
    }

})();
</script>

<?php get_footer(); ?>
