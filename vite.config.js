import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from "@tailwindcss/vite"
import fs from 'fs'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    tailwindcss(),
  ],

  server: {
    cors: true,
    https: false,   // disable HTTPS for dev
    host: 'localhost',
    port: 5173,
    watch: {
      ignored: ['**/vendor/**']
    }
  },
})

