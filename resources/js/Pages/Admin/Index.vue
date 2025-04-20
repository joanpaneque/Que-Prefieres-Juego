<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// Recibir las categorías del controlador
const props = defineProps({
  categories: Array
});

// Formulario para crear categorías
const createForm = useForm({
  name: ''
});

// Formulario para editar categorías
const editForm = useForm({
  id: null,
  name: ''
});

// Estado para mostrar/ocultar modal
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedCategory = ref(null);

// Métodos para gestionar categorías
const openCreateModal = () => {
  createForm.reset();
  showCreateModal.value = true;
};

const submitCreateForm = () => {
  createForm.post(route('admin.categories.store'), {
    onSuccess: () => {
      showCreateModal.value = false;
      createForm.reset();
    }
  });
};

const openEditModal = (category) => {
  editForm.id = category.id;
  editForm.name = category.name;
  showEditModal.value = true;
};

const submitEditForm = () => {
  editForm.put(route('admin.categories.update', { category: editForm.id }), {
    onSuccess: () => {
      showEditModal.value = false;
      editForm.reset();
    }
  });
};

const openDeleteModal = (category) => {
  selectedCategory.value = category;
  showDeleteModal.value = true;
};

const confirmDelete = () => {
  if (selectedCategory.value) {
    useForm().delete(route('admin.categories.delete', { category: selectedCategory.value.id }), {
      onSuccess: () => {
        showDeleteModal.value = false;
        selectedCategory.value = null;
      }
    });
  }
};
</script>

<template>
  <Head>
    <title>Panel de Administración</title>
  </Head>
  
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Panel de Administración</h1>
        <!-- Botón de Logout -->
        <Link 
          :href="route('logout')" 
          method="post" 
          as="button" 
          class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition"
        >
          Cerrar Sesión
        </Link>
      </div>
    </header>
    
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- Sección de categorías -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Categorías</h2>
            <button 
              @click="openCreateModal"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            >
              Crear Categoría
            </button>
          </div>
          
          <!-- Tabla de categorías -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="category in categories" :key="category.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <Link 
                      :href="route('admin.categories.show', { category: category.id })" 
                      class="text-indigo-600 hover:text-indigo-900 hover:underline"
                    >
                      {{ category.name }}
                    </Link>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button 
                      @click="openEditModal(category)" 
                      class="text-indigo-600 hover:text-indigo-900 mr-3"
                    >
                      Editar
                    </button>
                    <button 
                      @click="openDeleteModal(category)" 
                      class="text-red-600 hover:text-red-900"
                    >
                      Eliminar
                    </button>
                  </td>
                </tr>
                <tr v-if="!categories || categories.length === 0">
                  <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No hay categorías disponibles</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
    
    <!-- Modal para crear categoría -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Crear Nueva Categoría</h3>
        <form @submit.prevent="submitCreateForm">
          <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input 
              v-model="createForm.name" 
              type="text" 
              id="name" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
              required
            >
            <div v-if="createForm.errors.name" class="text-red-500 text-sm mt-1">{{ createForm.errors.name }}</div>
          </div>
          <div class="flex justify-end gap-3">
            <button 
              type="button" 
              @click="showCreateModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button 
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
              :disabled="createForm.processing"
            >
              Crear
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Modal para editar categoría -->
    <div v-if="showEditModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Editar Categoría</h3>
        <form @submit.prevent="submitEditForm">
          <div class="mb-4">
            <label for="edit-name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input 
              v-model="editForm.name" 
              type="text" 
              id="edit-name" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
              required
            >
            <div v-if="editForm.errors.name" class="text-red-500 text-sm mt-1">{{ editForm.errors.name }}</div>
          </div>
          <div class="flex justify-end gap-3">
            <button 
              type="button" 
              @click="showEditModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button 
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
              :disabled="editForm.processing"
            >
              Guardar
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Modal para confirmar eliminación -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmar Eliminación</h3>
        <p class="mb-4 text-gray-600">¿Estás seguro de que deseas eliminar la categoría "{{ selectedCategory?.name }}"?</p>
        <p class="mb-4 text-red-600 text-sm">Esta acción no se puede deshacer. Solo se pueden eliminar categorías sin preferencias asociadas.</p>
        <div class="flex justify-end gap-3">
          <button 
            type="button" 
            @click="showDeleteModal = false"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button 
            @click="confirmDelete"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
          >
            Eliminar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Add component-specific styles here */
</style> 