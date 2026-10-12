<script setup lang="ts">
const open = defineModel<boolean>({ required: true })

defineProps<{ title: string; text: string; loading?: boolean; confirmText?: string }>()
defineEmits<{ confirm: [] }>()
</script>

<template>
  <v-dialog v-model="open" max-width="420">
    <v-card :title="title" :text="text">
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="open = false">Cancelar</v-btn>
        <v-btn color="error" variant="flat" :loading="loading" @click="$emit('confirm')">{{ confirmText ?? 'Confirmar' }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
