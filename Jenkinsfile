pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    environment {
        APP_ENV = 'testing'
        XDEBUG_MODE = 'coverage'
        COMPOSER_NO_INTERACTION = '1'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-progress --prefer-dist'
                sh 'cp -n .env.example .env || true'
                sh 'php artisan key:generate --force'
            }
        }

        stage('Security Audit') {
            steps {
                // gagal (exit code != 0) bila ada kerentanan pada dependency
                sh 'composer audit --locked --abandoned=report'
            }
        }

        stage('Test & Coverage Gate') {
            steps {
                // gagal bila coverage app/ kurang dari 100%
                sh 'php -d pcov.enabled=1 artisan test --coverage --min=100'
            }
        }
    }

    post {
        always {
            cleanWs()
        }
    }
}
