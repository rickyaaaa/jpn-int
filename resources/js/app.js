import './bootstrap';

import Alpine from 'alpinejs';

const QUESTIONS_KEY = 'jpnInterviewQuestions';
const CANDIDATE_KEY = 'jpnInterviewCandidate';
const ANSWERS_KEY = 'jpnInterviewAnswers';
const AUTH_KEY = 'jpnInterviewAuth';

window.Alpine = Alpine;

window.startForm = function startForm() {
    return {
        username: 'admin',
        password: 'password',
        error: '',
        begin() {
            if (!this.username.trim() || !this.password.trim()) {
                this.error = 'Masukkan username dan password.';
                return;
            }

            if (this.username.trim() !== 'admin' || this.password !== 'password') {
                this.error = 'Username atau password tidak sesuai. Gunakan admin / password untuk testing.';
                return;
            }

            const candidate = {
                name: 'Admin Tester',
                username: this.username.trim(),
                role: 'Prototype tester',
                startedAt: new Date().toISOString(),
            };

            localStorage.setItem(AUTH_KEY, JSON.stringify({ username: candidate.username, loggedInAt: candidate.startedAt }));
            localStorage.setItem(CANDIDATE_KEY, JSON.stringify(candidate));
            localStorage.removeItem(ANSWERS_KEY);
            window.location.assign('/interview');
        },
    };
};

window.interviewPrototype = function interviewPrototype({ questions, resultsUrl, uploadUrl, csrfToken, initialAnsweredCount = 0 }) {
    return {
        questions,
        resultsUrl,
        uploadUrl,
        csrfToken,
        currentIndex: 0,
        permissionState: 'idle',
        recorderState: 'idle',
        processing: false,
        error: '',
        elapsedSeconds: 0,
        timerId: null,
        stream: null,
        recorder: null,
        chunks: [],
        recordedBlob: null,
        recordedFileName: '',
        recordedMimeType: '',
        audioUrl: '',
        answers: [],

        init() {
            localStorage.setItem(QUESTIONS_KEY, JSON.stringify(this.questions));
            this.answers = this.questions.filter((question) => question.answered);
            this.currentIndex = Math.min(initialAnsweredCount, this.questions.length - 1);

            if (initialAnsweredCount >= this.questions.length) {
                window.location.replace(this.resultsUrl);
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
                this.permissionState = 'unsupported';
                this.error = 'Browser ini belum mendukung perekaman suara langsung.';
            }
        },

        get currentQuestion() {
            return this.questions[this.currentIndex] || this.questions[0];
        },

        get progressPercent() {
            return Math.round(((this.currentIndex + 1) / this.questions.length) * 100);
        },

        get formattedTime() {
            const minutes = String(Math.floor(this.elapsedSeconds / 60)).padStart(2, '0');
            const seconds = String(this.elapsedSeconds % 60).padStart(2, '0');
            return `${minutes}:${seconds}`;
        },

        async requestMic() {
            this.error = '';

            if (this.permissionState === 'unsupported') {
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.permissionState = 'granted';
            } catch (error) {
                this.permissionState = 'denied';
                this.error = 'Izin mikrofon ditolak. Aktifkan izin mikrofon di browser untuk melanjutkan.';
            }
        },

        async startRecording() {
            if (this.permissionState !== 'granted') {
                await this.requestMic();
            }

            if (!this.stream || this.permissionState !== 'granted') {
                return;
            }

            this.resetRecording();
            this.chunks = [];
            const preferredMimeType = this.preferredMimeType();
            const options = preferredMimeType ? { mimeType: preferredMimeType } : {};

            try {
                this.recorder = new MediaRecorder(this.stream, options);
            } catch (error) {
                this.recorder = new MediaRecorder(this.stream);
            }

            this.recorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.chunks.push(event.data);
                }
            };
            this.recorder.onstop = () => {
                const mimeType = this.recorder.mimeType || preferredMimeType || this.chunks[0]?.type || 'audio/webm';
                const blob = new Blob(this.chunks, { type: mimeType });

                if (blob.size < 1024) {
                    this.error = 'Rekaman terlalu kecil atau kosong. Coba rekam lagi dengan suara yang lebih jelas.';
                    this.recorderState = 'idle';
                    return;
                }

                this.recordedBlob = blob;
                this.recordedMimeType = mimeType;
                this.recordedFileName = `answer-${this.currentQuestion.number}.${this.extensionForMimeType(mimeType)}`;
                this.audioUrl = URL.createObjectURL(blob);
                this.recorderState = 'recorded';
            };

            this.recorder.start();
            this.recorderState = 'recording';
            this.timerId = window.setInterval(() => {
                this.elapsedSeconds += 1;
            }, 1000);
        },

        stopRecording() {
            if (this.recorder && this.recorder.state === 'recording') {
                this.recorder.stop();
            }
            window.clearInterval(this.timerId);
            this.timerId = null;
        },

        resetRecording() {
            window.clearInterval(this.timerId);
            this.timerId = null;
            this.elapsedSeconds = 0;
            this.recorderState = 'idle';
            this.processing = false;
            this.error = '';
            this.recordedBlob = null;
            this.recordedFileName = '';
            this.recordedMimeType = '';

            if (this.audioUrl) {
                URL.revokeObjectURL(this.audioUrl);
            }
            this.audioUrl = '';
        },

        async submitAnswer() {
            if (!this.recordedBlob || this.processing) {
                return;
            }

            this.processing = true;
            this.error = '';

            const formData = new FormData();
            formData.append('question_id', this.currentQuestion.id);
            formData.append('duration_seconds', this.elapsedSeconds);
            formData.append('audio_mime_type', this.recordedMimeType || this.recordedBlob.type || 'application/octet-stream');
            formData.append('audio', this.recordedBlob, this.recordedFileName || `answer-${this.currentQuestion.number}.webm`);

            try {
                const response = await fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal memproses jawaban.');
                }

                this.answers = [...this.answers, data.answer];

                if (data.is_complete) {
                    this.stopTracks();
                    window.location.assign(data.results_url || this.resultsUrl);
                    return;
                }

                this.currentIndex = Math.min(data.answered_count, this.questions.length - 1);
                this.resetRecording();
            } catch (error) {
                this.error = error.message;
                this.processing = false;
            }
        },

        stopTracks() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
            }
        },

        preferredMimeType() {
            const candidates = [
                'audio/webm;codecs=opus',
                'audio/webm',
                'video/webm;codecs=opus',
                'video/webm',
                'audio/mp4',
                'video/mp4',
                'audio/ogg;codecs=opus',
                'audio/ogg',
            ];

            return candidates.find((mimeType) => MediaRecorder.isTypeSupported?.(mimeType)) || '';
        },

        extensionForMimeType(mimeType) {
            const cleanMimeType = (mimeType || '').split(';')[0].toLowerCase();
            const extensions = {
                'audio/webm': 'webm',
                'video/webm': 'webm',
                'audio/mp4': 'mp4',
                'video/mp4': 'mp4',
                'audio/mpeg': 'mp3',
                'audio/ogg': 'ogg',
                'video/ogg': 'ogg',
                'audio/wav': 'wav',
                'audio/x-wav': 'wav',
                'audio/aac': 'aac',
                'audio/x-aac': 'aac',
                'audio/m4a': 'm4a',
                'audio/x-m4a': 'm4a',
            };

            return extensions[cleanMimeType] || 'webm';
        },

        isLoggedIn() {
            return Boolean(this.readJson(AUTH_KEY, null)?.username);
        },

        readJson(key, fallback) {
            try {
                return JSON.parse(localStorage.getItem(key)) || fallback;
            } catch (error) {
                return fallback;
            }
        },
    };
};

window.resultsPrototype = function resultsPrototype({ questions }) {
    return {
        questions,
        candidate: null,
        answers: [],

        init() {
            if (!this.isLoggedIn()) {
                window.location.replace('/');
                return;
            }

            this.candidate = this.readJson(CANDIDATE_KEY, {
                name: 'Kandidat',
                username: '',
                role: '',
                startedAt: null,
            });
            this.answers = this.readJson(ANSWERS_KEY, []);
        },

        get completedCount() {
            return this.answers.length;
        },

        get averageScore() {
            if (!this.answers.length) {
                return 0;
            }

            const total = this.answers.reduce((sum, answer) => sum + Number(answer.score || 0), 0);
            return Math.round(total / this.answers.length);
        },

        get summaryText() {
            if (this.averageScore >= 88) {
                return 'Kandidat menunjukkan kesiapan komunikasi Jepang yang kuat untuk sesi awal.';
            }

            if (this.averageScore >= 78) {
                return 'Kandidat cukup siap, dengan beberapa area latihan pada pelafalan dan tata bahasa dasar.';
            }

            return 'Kandidat membutuhkan latihan tambahan sebelum masuk proses wawancara Jepang lanjutan.';
        },

        restart() {
            localStorage.removeItem(ANSWERS_KEY);
            window.location.assign('/interview');
        },

        logout() {
            localStorage.removeItem(AUTH_KEY);
            localStorage.removeItem(CANDIDATE_KEY);
            localStorage.removeItem(ANSWERS_KEY);
            window.location.assign('/');
        },

        isLoggedIn() {
            return Boolean(this.readJson(AUTH_KEY, null)?.username);
        },

        readJson(key, fallback) {
            try {
                return JSON.parse(localStorage.getItem(key)) || fallback;
            } catch (error) {
                return fallback;
            }
        },
    };
};

Alpine.start();
