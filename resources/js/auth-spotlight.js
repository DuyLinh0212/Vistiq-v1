export const createBeamGeometry = (origin, target, length) => {
    const angle = Math.hypot(target.x - origin.x, target.y - origin.y) < 1
        ? Math.PI
        : Math.atan2(target.y - origin.y, target.x - origin.x);
    const halfAngle = Math.PI / 12;

    return {
        angle,
        points: [
            origin,
            { x: origin.x + Math.cos(angle - halfAngle) * length, y: origin.y + Math.sin(angle - halfAngle) * length },
            { x: origin.x + Math.cos(angle + halfAngle) * length, y: origin.y + Math.sin(angle + halfAngle) * length },
        ],
        end: { x: origin.x + Math.cos(angle) * length, y: origin.y + Math.sin(angle) * length },
    };
};

export const beamClipPath = (points, rectangle) => `polygon(${points.map((point) => `${point.x - rectangle.left}px ${point.y - rectangle.top}px`).join(', ')})`;

export const initializeAuthSpotlights = () => {
    document.querySelectorAll('[data-auth-spotlight]').forEach((shell) => {
        const fields = [...shell.querySelectorAll('[data-spotlight-password]')].map((wrapper) => ({
            input: wrapper.querySelector('input'),
            entry: wrapper.querySelector('.spotlight-password-entry'),
            reveal: wrapper.querySelector('.spotlight-password-reveal'),
            content: wrapper.querySelector('.spotlight-password-reveal > span'),
            toggle: wrapper.querySelector('[data-spotlight-toggle]'),
        }));
        const beam = shell.querySelector('[data-spotlight-beam]');
        const cone = beam.querySelector('[data-spotlight-cone]');
        const gradient = beam.querySelector('linearGradient');
        const help = shell.querySelector('.spotlight-password-help');
        const pointerMedia = window.matchMedia('(hover: hover) and (pointer: fine)');
        const reducedMotionMedia = window.matchMedia('(prefers-reduced-motion: reduce)');
        const supportsBeam = CSS.supports('clip-path', 'polygon(0px 0px, 1px 0px, 0px 1px)');
        shell.classList.add('is-spotlight-enhanced');
        let activeField = null;
        let pointer = null;
        let frame = null;

        const setSky = (isNight) => {
            const sky = isNight ? 'night' : 'day';

            document.documentElement.dataset.spotlightSky = sky;
            document.body.dataset.spotlightSky = sky;
            document.querySelector('meta[name="theme-color"]')?.setAttribute('content', isNight ? '#07121c' : '#e7e8eb');
        };

        const clearReveal = (field) => {
            field.content.textContent = '';
            field.reveal.classList.remove('is-revealing');
            field.reveal.style.removeProperty('clip-path');
        };

        const setPasswordVisible = (field, visible) => {
            const start = field.input.selectionStart;
            const end = field.input.selectionEnd;
            const scrollLeft = field.input.scrollLeft;
            field.input.type = visible ? 'text' : 'password';

            if (start !== null && end !== null) {
                field.input.setSelectionRange(start, end);
            }

            field.input.scrollLeft = scrollLeft;
        };

        const stopLight = () => {
            activeField = null;
            pointer = null;
            setSky(false);
            shell.classList.remove('is-spotlight-on');
            cone.removeAttribute('points');

            if (frame !== null) {
                cancelAnimationFrame(frame);
                frame = null;
            }

            fields.forEach((field) => {
                setPasswordVisible(field, false);
                field.toggle.setAttribute('aria-pressed', 'false');
                field.toggle.setAttribute('aria-label', `Bật đèn soi ${field.input.labels[0].textContent.toLowerCase()}`);
                field.toggle.querySelector('[data-spotlight-lamp]').removeAttribute('transform');
                clearReveal(field);
            });
        };

        const render = () => {
            frame = null;

            if (!activeField || !pointer) {
                return;
            }

            const bounds = beam.getBoundingClientRect();
            const lampBounds = activeField.toggle.getBoundingClientRect();
            const center = { x: lampBounds.left + lampBounds.width / 2, y: lampBounds.top + lampBounds.height / 2 };
            const length = Math.hypot(bounds.width, bounds.height);
            const direction = createBeamGeometry(center, pointer, length).angle;
            const origin = { x: center.x + Math.cos(direction) * 7, y: center.y + Math.sin(direction) * 7 };
            const aim = Math.hypot(pointer.x - center.x, pointer.y - center.y) < 14
                ? { x: origin.x + Math.cos(direction) * length, y: origin.y + Math.sin(direction) * length }
                : pointer;
            const geometry = createBeamGeometry(origin, aim, length);
            const rectangles = fields.map((field) => field.entry.getBoundingClientRect());

            beam.setAttribute('viewBox', `0 0 ${bounds.width} ${bounds.height}`);
            cone.setAttribute('points', geometry.points.map((point) => `${point.x - bounds.left},${point.y - bounds.top}`).join(' '));
            gradient.setAttribute('x1', origin.x - bounds.left);
            gradient.setAttribute('y1', origin.y - bounds.top);
            gradient.setAttribute('x2', geometry.end.x - bounds.left);
            gradient.setAttribute('y2', geometry.end.y - bounds.top);
            activeField.toggle.querySelector('[data-spotlight-lamp]').setAttribute('transform', `rotate(${geometry.angle * 180 / Math.PI - 180} 12 12)`);

            fields.forEach((field, index) => {
                if (field !== activeField || field.input.type !== 'password' || field.input.value === '') {
                    clearReveal(field);

                    return;
                }

                if (field.content.textContent !== field.input.value) {
                    field.content.textContent = field.input.value;
                }

                field.content.style.transform = `translate(${-field.input.scrollLeft}px, -50%)`;
                field.reveal.style.clipPath = beamClipPath(geometry.points, rectangles[index]);
                field.reveal.classList.add('is-revealing');
            });

            if (!reducedMotionMedia.matches) {
                frame = requestAnimationFrame(render);
            }
        };

        const requestRender = () => {
            if (activeField && frame === null) {
                if (reducedMotionMedia.matches) {
                    render();
                } else {
                    frame = requestAnimationFrame(render);
                }
            }
        };

        fields.forEach((field) => {
            field.toggle.addEventListener('click', (event) => {
                if (activeField === field) {
                    stopLight();

                    return;
                }

                stopLight();
                activeField = field;
                setSky(true);
                field.toggle.setAttribute('aria-pressed', 'true');
                field.toggle.setAttribute('aria-label', `Tắt đèn soi ${field.input.labels[0].textContent.toLowerCase()}`);

                if (!pointerMedia.matches || !supportsBeam || event.detail === 0) {
                    setPasswordVisible(field, true);

                    return;
                }

                const bounds = field.entry.getBoundingClientRect();
                pointer = { x: bounds.left + bounds.width / 2, y: bounds.top + bounds.height / 2 };
                shell.classList.add('is-spotlight-on');
                requestRender();
            });

            ['input', 'change', 'scroll', 'focus'].forEach((eventName) => field.input.addEventListener(eventName, requestRender));
        });

        shell.addEventListener('pointermove', (event) => {
            if (!activeField || !pointer || event.pointerType === 'touch') {
                return;
            }

            pointer = { x: event.clientX, y: event.clientY };
            requestRender();
        });
        shell.addEventListener('pointerleave', () => {
            if (pointer) {
                stopLight();
            }
        });
        shell.addEventListener('pointercancel', stopLight);
        const form = shell.querySelector('form');
        shell.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                stopLight();
            }
        });
        form?.addEventListener('submit', () => {
            stopLight();
        });
        window.addEventListener('blur', () => {
            stopLight();
        });
        window.addEventListener('pagehide', () => {
            stopLight();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopLight();
            }
        });
        window.addEventListener('resize', requestRender);
        const syncPointerMode = () => {
            stopLight();
            shell.classList.toggle('is-spotlight-ready', supportsBeam && pointerMedia.matches);
            help.textContent = supportsBeam && pointerMedia.matches
                ? 'Di chuyển chuột để soi mật khẩu trong vùng sáng.'
                : 'Bấm biểu tượng để hiện hoặc che mật khẩu.';
        };
        pointerMedia.addEventListener('change', syncPointerMode);
        syncPointerMode();
        if (form) {
            initializePasswordValidation(form);
        }
    });
};

export const initializePasswordValidation = (form) => {
    if (!form) {
        return;
    }

    const passwordInput = form.querySelector('input[name="password"]');
    const confirmInput = form.querySelector('input[name="password_confirmation"]');
    const tracker = form.querySelector('[data-password-rules]');

    if (!passwordInput || !tracker) {
        return;
    }

    const minLength = parseInt(tracker.dataset.min || '8', 10);
    const countEl = tracker.querySelector('[data-rule-count]');
    const messageEl = tracker.querySelector('[data-rule-message]');
    const progressEl = tracker.querySelector('.password-rule-progress');

    const getErrorEl = (input) => {
        let error = form.querySelector(`#${input.id}-error`);
        if (!error) {
            error = document.createElement('span');
            error.className = 'auth-field-error';
            error.id = `${input.id}-error`;
            error.setAttribute('role', 'alert');
            const field = input.closest('.auth-field');
            field?.appendChild(error);
        }
        return error;
    };

    const clearError = (input) => {
        const error = form.querySelector(`#${input.id}-error`);
        if (error) {
            error.textContent = '';
            error.style.display = 'none';
        }
        input.setAttribute('aria-invalid', 'false');
        input.setAttribute('aria-describedby', 'spotlight-password-help');
    };

    const showError = (input, message) => {
        const error = getErrorEl(input);
        error.textContent = message;
        error.style.display = '';
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', `spotlight-password-help ${input.id}-error`);
    };

    const updateStatus = () => {
        const value = passwordInput.value;
        const len = value.length;
        const hasLetter = /[A-Za-z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const isLengthMet = len >= minLength;
        const isValid = isLengthMet && hasLetter && hasNumber;

        if (countEl && messageEl && progressEl) {
            const percent = Math.min(100, Math.round((len / minLength) * 100));
            progressEl.style.width = `${percent}%`;

            tracker.classList.remove('is-empty', 'is-progress', 'is-warning', 'is-valid');

            if (len === 0) {
                tracker.classList.add('is-empty');
                countEl.textContent = `0/${minLength} ký tự`;
                messageEl.textContent = `Tối thiểu ${minLength} ký tự, gồm cả chữ và số`;
            } else if (!isLengthMet) {
                tracker.classList.add('is-progress');
                countEl.textContent = `${len}/${minLength} ký tự`;
                const remaining = minLength - len;
                messageEl.textContent = `Còn thiếu ${remaining} ký tự nữa`;
            } else if (!hasLetter) {
                tracker.classList.add('is-warning');
                countEl.textContent = `${len}/${minLength} ký tự`;
                messageEl.textContent = 'Cần thêm chữ cái (a-z)';
            } else if (!hasNumber) {
                tracker.classList.add('is-warning');
                countEl.textContent = `${len}/${minLength} ký tự`;
                messageEl.textContent = 'Cần thêm chữ số (0-9)';
            } else {
                tracker.classList.add('is-valid');
                countEl.textContent = `✓ ${len}/${minLength} ký tự`;
                messageEl.textContent = 'Mật khẩu đủ điều kiện';
            }
        }

        if (isValid) {
            clearError(passwordInput);
        }

        if (confirmInput && confirmInput.value) {
            if (confirmInput.value === value) {
                clearError(confirmInput);
            }
        }
    };

    passwordInput.addEventListener('input', updateStatus);

    if (confirmInput) {
        confirmInput.addEventListener('input', () => {
            if (confirmInput.value && confirmInput.value === passwordInput.value) {
                clearError(confirmInput);
            }
        });
    }

    form.addEventListener('submit', (event) => {
        const value = passwordInput.value;
        const len = value.length;
        const hasLetter = /[A-Za-z]/.test(value);
        const hasNumber = /[0-9]/.test(value);

        if (len < minLength) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showError(passwordInput, `Mật khẩu cần ít nhất ${minLength} ký tự.`);
            passwordInput.focus();
            return;
        }

        if (!hasLetter) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showError(passwordInput, 'Mật khẩu cần có ít nhất một chữ cái.');
            passwordInput.focus();
            return;
        }

        if (!hasNumber) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showError(passwordInput, 'Mật khẩu cần có ít nhất một chữ số.');
            passwordInput.focus();
            return;
        }

        if (confirmInput && confirmInput.value !== value) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showError(confirmInput, 'Hai mật khẩu chưa khớp.');
            confirmInput.focus();
            return;
        }
    });

    if (passwordInput.value) {
        updateStatus();
    }
};
