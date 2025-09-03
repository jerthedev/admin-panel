<template>
  <div class="mobile-profile-menu">
    <div class="profile-header">
      <img
        :src="user?.avatar || avatarPlaceholder"
        :alt="user?.name"
        class="w-20 h-20 rounded-full"
      />
      <div class="mt-3">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ user?.name || 'User' }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
      </div>
    </div>

    <nav class="profile-nav">
      <a
        v-for="item in menuItems"
        :key="item.name"
        :href="item.href"
        class="profile-nav-item"
        @click="handleItemClick(item)"
      >
        <component :is="item.icon" class="w-5 h-5 text-gray-400" />
        <span>{{ item.name }}</span>
      </a>
    </nav>

    <div class="profile-footer">
      <button
        @click="handleLogout"
        class="w-full flex items-center gap-3 px-4 py-3 text-left text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
      >
        <ArrowLeftOnRectangleIcon class="w-5 h-5" />
        <span>Sign out</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import {
  UserIcon,
  Cog6ToothIcon,
  BellIcon,
  ShieldCheckIcon,
  ArrowLeftOnRectangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  onClose: {
    type: Function,
    default: null
  }
})

const emit = defineEmits(['close'])

const page = usePage()
const user = computed(() => page.props.auth?.user)

const avatarPlaceholder = computed(() => {
  const name = user.value?.name || 'User'
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=3b82f6&color=fff`
})

const menuItems = [
  { name: 'Profile', href: '/admin/profile', icon: UserIcon },
  { name: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon },
  { name: 'Notifications', href: '/admin/notifications', icon: BellIcon },
  { name: 'Security', href: '/admin/security', icon: ShieldCheckIcon },
]

const handleItemClick = (item) => {
  if (props.onClose) props.onClose()
  emit('close')
}

const handleLogout = () => {
  router.post('/logout')
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.mobile-profile-menu {
  @apply h-full flex flex-col;
}

.profile-header {
  @apply flex flex-col items-center p-6 border-b border-gray-200 dark:border-gray-700;
}

.profile-nav {
  @apply flex-1 py-2;
}

.profile-nav-item {
  @apply flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800;
  @apply text-gray-700 dark:text-gray-300;
}

.profile-footer {
  @apply border-t border-gray-200 dark:border-gray-700;
}
</style>