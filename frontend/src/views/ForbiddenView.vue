<template>
  <div class="text-center py-16">
    <v-icon icon="mdi-lock-outline" size="64" color="accent" />
    <h1 class="text-headline-small mt-4">No tienes permiso para ver esta sección</h1>
    <p class="text-medium-emphasis mt-2">Si crees que es un error, pide a un administrador que revise tu rol.</p>
    <v-btn :to="{ name: 'home' }" color="primary" class="mt-6">Ir al inicio</v-btn>
  </div>
</template>
