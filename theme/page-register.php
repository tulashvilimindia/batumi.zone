<?php
/**
 * Template Name: Register
 * Description: User registration page for service posters
 *
 * @package Batumi_Theme
 * @since 0.3.0
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/my-listings/'));
    exit;
}

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';
?>

<main id="primary" class="site-main auth-page">
    <div class="container">
        <div class="auth-wrapper">

            <div class="auth-card">
                <h1 class="auth-title">
                    <?php
                    if ($current_lang === 'ru') {
                        echo 'Регистрация';
                    } elseif ($current_lang === 'en') {
                        echo 'Register';
                    } else {
                        echo 'რეგისტრაცია';
                    }
                    ?>
                </h1>

                <p class="auth-subtitle">
                    <?php
                    if ($current_lang === 'ru') {
                        echo 'Создайте аккаунт, чтобы публиковать услуги';
                    } elseif ($current_lang === 'en') {
                        echo 'Create an account to post services';
                    } else {
                        echo 'შექმენით ანგარიში სერვისების გამოსაქვეყნებლად';
                    }
                    ?>
                </p>

                <div id="register-messages" class="auth-messages"></div>

                <form id="register-form" class="auth-form" method="post">

                    <div class="form-group">
                        <label for="username" class="form-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Имя пользователя *';
                            } elseif ($current_lang === 'en') {
                                echo 'Username *';
                            } else {
                                echo 'მომხმარებლის სახელი *';
                            }
                            ?>
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            required
                            minlength="3"
                            pattern="[a-zA-Z0-9_]+"
                            placeholder="<?php echo $current_lang === 'ru' ? 'минимум 3 символа' : ($current_lang === 'en' ? 'minimum 3 characters' : 'მინიმუმ 3 სიმბოლო'); ?>"
                        >
                        <small class="form-help">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Только латинские буквы, цифры и подчеркивание';
                            } elseif ($current_lang === 'en') {
                                echo 'Only letters, numbers and underscore';
                            } else {
                                echo 'მხოლოდ ლათინური ასოები, ციფრები და ხაზგასმა';
                            }
                            ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Email *';
                            } elseif ($current_lang === 'en') {
                                echo 'Email *';
                            } else {
                                echo 'ელფოსტა *';
                            }
                            ?>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            required
                            placeholder="example@email.com"
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Телефон *';
                            } elseif ($current_lang === 'en') {
                                echo 'Phone *';
                            } else {
                                echo 'ტელეფონი *';
                            }
                            ?>
                        </label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-input"
                            required
                            pattern="[0-9+\-\s()]+"
                            placeholder="+995 555 12 34 56"
                        >
                        <small class="form-help">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Требуется для публикации объявлений';
                            } elseif ($current_lang === 'en') {
                                echo 'Required to publish listings';
                            } else {
                                echo 'საჭიროა განცხადებების გამოსაქვეყნებლად';
                            }
                            ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="whatsapp" class="form-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'WhatsApp (опционально)';
                            } elseif ($current_lang === 'en') {
                                echo 'WhatsApp (optional)';
                            } else {
                                echo 'WhatsApp (არასავალდებულო)';
                            }
                            ?>
                        </label>
                        <input
                            type="tel"
                            id="whatsapp"
                            name="whatsapp"
                            class="form-input"
                            pattern="[0-9+\-\s()]+"
                            placeholder="+995 555 12 34 56"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Пароль *';
                            } elseif ($current_lang === 'en') {
                                echo 'Password *';
                            } else {
                                echo 'პაროლი *';
                            }
                            ?>
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            required
                            minlength="8"
                            placeholder="<?php echo $current_lang === 'ru' ? 'минимум 8 символов' : ($current_lang === 'en' ? 'minimum 8 characters' : 'მინიმუმ 8 სიმბოლო'); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password_confirm" class="form-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Подтвердите пароль *';
                            } elseif ($current_lang === 'en') {
                                echo 'Confirm Password *';
                            } else {
                                echo 'დაადასტურეთ პაროლი *';
                            }
                            ?>
                        </label>
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            class="form-input"
                            required
                            minlength="8"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="register-submit">
                        <span class="btn-text">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Зарегистрироваться';
                            } elseif ($current_lang === 'en') {
                                echo 'Register';
                            } else {
                                echo 'რეგისტრაცია';
                            }
                            ?>
                        </span>
                        <span class="btn-spinner" style="display: none;">⏳</span>
                    </button>

                    <input type="hidden" name="action" value="register_user">
                    <?php wp_nonce_field('register_user_nonce', 'register_nonce'); ?>
                </form>

                <div class="auth-footer">
                    <p>
                        <?php
                        if ($current_lang === 'ru') {
                            echo 'Уже есть аккаунт? ';
                        } elseif ($current_lang === 'en') {
                            echo 'Already have an account? ';
                        } else {
                            echo 'უკვე გაქვთ ანგარიში? ';
                        }
                        ?>
                        <a href="<?php echo home_url('/login/'); ?>">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Войти';
                            } elseif ($current_lang === 'en') {
                                echo 'Login';
                            } else {
                                echo 'შესვლა';
                            }
                            ?>
                        </a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
jQuery(document).ready(function($) {
    $('#register-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $('#register-submit');
        var $btnText = $submitBtn.find('.btn-text');
        var $btnSpinner = $submitBtn.find('.btn-spinner');
        var $messages = $('#register-messages');

        // Validate password match
        var password = $('#password').val();
        var passwordConfirm = $('#password_confirm').val();

        if (password !== passwordConfirm) {
            $messages.html('<div class="message message-error"><?php echo $current_lang === "ru" ? "Пароли не совпадают" : ($current_lang === "en" ? "Passwords do not match" : "პაროლები არ ემთხვევა"); ?></div>');
            return;
        }

        // Disable submit button
        $submitBtn.prop('disabled', true);
        $btnText.hide();
        $btnSpinner.show();
        $messages.empty();

        // Prepare data
        var formData = {
            username: $('#username').val(),
            email: $('#email').val(),
            phone: $('#phone').val(),
            whatsapp: $('#whatsapp').val(),
            password: password
        };

        // Make AJAX request to REST API
        $.ajax({
            url: '<?php echo rest_url('batumizone/v1/auth/register'); ?>',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                $messages.html('<div class="message message-success"><?php echo $current_lang === "ru" ? "Регистрация успешна! Перенаправление..." : ($current_lang === "en" ? "Registration successful! Redirecting..." : "რეგისტრაცია წარმატებულია! გადამისამართება..."); ?></div>');

                // Redirect to my listings after 1 second
                setTimeout(function() {
                    window.location.href = '<?php echo home_url('/my-listings/'); ?>';
                }, 1000);
            },
            error: function(xhr) {
                var errorMessage = '<?php echo $current_lang === "ru" ? "Ошибка регистрации" : ($current_lang === "en" ? "Registration error" : "რეგისტრაციის შეცდომა"); ?>';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                $messages.html('<div class="message message-error">' + errorMessage + '</div>');

                // Re-enable submit button
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnSpinner.hide();
            }
        });
    });
});
</script>

<?php
get_footer();
