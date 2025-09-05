<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const showLoginModal = ref(false);
</script>

<template>
  <Head title="Welcome">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Kaushan+Script&display=swap" rel="stylesheet" />
  </Head>

  <!-- HERO BACKGROUND SECTION -->
  <div class="relative flex min-h-screen flex-col bg-black text-white font-sans">
    
    <!-- Overlay with gradient -->
    <div class="absolute inset-0 bg-gradient-to-b from-black/80 to-black/90"></div>

    <!-- Content Wrapper -->
    <div class="relative z-10 flex flex-col items-center justify-between w-full max-w-6xl mx-auto min-h-screen px-6">

      <!-- HEADER -->
      <header class="w-full py-6 flex items-center justify-between">
        <!-- Brand Logo -->
        <div class="text-3xl tracking-tight text-blue-500 font-bold" style="font-family: 'Kaushan Script', cursive;">
          ServeWise
        </div>

        <!-- Links -->
        <div class="flex gap-4 items-center">
          <Link v-if="$page.props.auth.user" 
                :href="route('dashboard')"
                class="px-4 py-2 border border-blue-500 text-blue-500 rounded-lg hover:bg-blue-500 hover:text-white transition">
            Dashboard
          </Link>

          <template v-else>
            <button @click="showLoginModal = true"
                    class="px-4 py-2 rounded-lg hover:underline text-white transition">
              Log in
            </button>
            <Link :href="route('register')"
                  class="px-4 py-2 border border-blue-500 text-blue-500 rounded-lg hover:bg-blue-500 hover:text-white transition">
              Register
            </Link>
          </template>
        </div>
      </header>

      <!-- HOME SECTION -->
      <section class="flex flex-col items-center justify-center flex-grow text-center px-6">
        <h1 class="text-6xl md:text-8xl font-extrabold text-white mb-4 leading-tight">
          Welcome to <span class="text-blue-500">ServeWise</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-300 mb-8 font-medium">
          Manage your restaurant smarter, faster, and with style.<br/>
          Everything you need to run a modern, professional restaurant.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
          <Link :href="route('register')" 
                class="bg-blue-500 text-white px-8 py-3 rounded-lg font-semibold shadow-md hover:bg-blue-600 transition">
            Get Started
          </Link>
          <a href="#features"
             class="border border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-black transition">
            See Features
          </a>
        </div>
      </section>
    </div>
  </div>

  <!-- LOGIN MODAL -->
  <div v-if="showLoginModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg w-[90%] max-w-md p-6">
      <h2 class="text-2xl font-bold mb-4 text-center text-blue-500">Login</h2>

      <div class="flex flex-col gap-4">
        <Link :href="route('login', { role: 'owner' })"
              class="w-full px-4 py-3 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold text-center shadow hover:opacity-90 transition">
          Login as Owner
        </Link>

        <Link :href="route('admin.login', { role: 'admin' })"
              class="w-full px-4 py-3 rounded-lg bg-gradient-to-r from-gray-700 to-black text-white font-semibold text-center shadow hover:opacity-90 transition">
          Login as Administrator
        </Link>
      </div>

      <button @click="showLoginModal = false"
              class="mt-6 w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
        Cancel
      </button>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="py-6 text-center w-full text-gray-400 text-sm bg-black">
    &copy; {{ new Date().getFullYear() }} ServeWise — Restaurant Platform
    <span class="mx-2 text-blue-500">|</span>
    <a href="#" class="underline hover:text-blue-400">Contact</a>
  </footer>
</template>
