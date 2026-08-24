module.exports = {
  apps: [
    {
      name: 'seal-daemon',
      script: 'daemon/bot-daemon.js',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '500M',
      env: {
        NODE_ENV: 'production'
      }
    },
    {
      name: 'seal-laravel',
      script: 'artisan',
      args: 'serve --host=127.0.0.1 --port=8000',
      interpreter: 'php',
      instances: 1,
      autorestart: true,
      watch: false
    }
  ]
};
