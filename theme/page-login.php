<?php
/**
 * Template Name: Login
 * Description: User login page for service posters
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
                        echo 'Вход';
                    } elseif ($current_lang === 'en') {
                        echo 'Login';
                    } else {
                        echo 'შესვლა';
                    }
                    ?>
                </h1>

                <p class="auth-subtitle">
                    <?php
                    if ($current_lang === 'ru') {
                        echo 'Войдите в свой аккаунт';
                    } elseif ($current_lang === 'en') {
                        echo 'Sign in to your account';
                    } else {
                        echo 'შედით თქვენს ანგარიშში';
                    }
                    ?>
                </p>

                <div id="login-messages" class="auth-messages"></div>

                <form id="login-form" class="auth-form" method="post">

                    <div class="form-group">
                        <label for="username" class="form-label">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Имя пользователя или Email *';
                            } elseif ($current_lang === 'en') {
                                echo 'Username or Email *';
                            } else {
                                echo 'მომხმარებლის სახელი ან ელფოსტა *';
                            }
                            ?>
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            required
                            autocomplete="username"
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
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="form-group form-checkbox">
                        <label>
                            <input type="checkbox" name="remember" value="1">
                            <span>
                                <?php
                                if ($current_lang === 'ru') {
                                    echo 'Запомнить меня';
                                } elseif ($current_lang === 'en') {
                                    echo 'Remember me';
                                } else {
                                    echo 'დამახსოვრება';
                                }
                                ?>
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="login-submit">
                        <span class="btn-text">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Войти';
                            } elseif ($current_lang === 'en') {
                                echo 'Login';
                            } else {
                                echo 'შესვლა';
                            }
                            ?>
                        </span>
                        <span class="btn-spinner" style="display: none;">⏳</span>
                    </button>

                    <input type="hidden" name="action" value="login_user">
                    <?php wp_nonce_field('login_user_nonce', 'login_nonce'); ?>
                </form>

                <div class="auth-footer">
                    <p>
                        <?php
                        if ($current_lang === 'ru') {
                            echo 'Нет аккаунта? ';
                        } elseif ($current_lang === 'en') {
                            echo 'Don\'t have an account? ';
                        } else {
                            echo 'არ გაქვთ ანგარიში? ';
                        }
                        ?>
                        <a href="<?php echo home_url('/register/'); ?>">
                            <?php
                            if ($current_lang === 'ru') {
                                echo 'Зарегистрироваться';
                            } elseif ($current_lang === 'en') {
                                echo 'Register';
                            } else {
                                echo 'რეგისტრაცია';
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
    $('#login-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $('#login-submit');
        var $btnText = $submitBtn.find('.btn-text');
        var $btnSpinner = $submitBtn.find('.btn-spinner');
        var $messages = $('#login-messages');

        // Disable submit button
        $submitBtn.prop('disabled', true);
        $btnText.hide();
        $btnSpinner.show();
        $messages.empty();

        // Prepare data
        var formData = {
            username: $('#username').val(),
            password: $('#password').val()
        };

        // Make AJAX request to REST API
        $.ajax({
            url: '<?php echo rest_url('batumizone/v1/auth/login'); ?>',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                $messages.html('<div class="message message-success"><?php echo $current_lang === "ru" ? "Вход выполнен! Перенаправление..." : ($current_lang === "en" ? "Login successful! Redirecting..." : "შესვლა წარმატებულია! გადამისამართება..."); ?></div>');

                // Redirect to my listings after 1 second
                setTimeout(function() {
                    window.location.href = '<?php echo home_url('/my-listings/'); ?>';
                }, 1000);
            },
            error: function(xhr) {
                var errorMessage = '<?php echo $current_lang === "ru" ? "Неверное имя пользователя или пароль" : ($current_lang === "en" ? "Invalid username or password" : "არასწორი მომხმარებელი ან პაროლი"); ?>';

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
