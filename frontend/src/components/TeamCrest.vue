<script setup lang="ts">
withDefaults(defineProps<{ src?: string | null; size?: number }>(), { size: 32 })
</script>

<template>
  <v-avatar :size="size" rounded="0">
    <v-img v-if="src" :src="src" alt="" />
    <v-icon v-else icon="mdi-shield-outline" color="grey" />
  </v-avatar>
</template>
