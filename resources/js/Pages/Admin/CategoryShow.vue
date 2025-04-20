<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  category: Object,
  preferences: Array, // Recibir las preferencias
  errors: Object // Recibir errores de validación
});

// --- State for Modals ---
const showCreatePreferenceModal = ref(false);
const showEditPreferenceModal = ref(false);
const showDeletePreferenceModal = ref(false);
const selectedPreference = ref(null);

// --- Forms ---
const createPreferenceForm = useForm({
  preference1: '',
  preference2: ''
});

const editPreferenceForm = useForm({
  id: null,
  preference1: '',
  preference2: ''
});

// --- Modal Actions ---
const openCreatePreferenceModal = () => {
  createPreferenceForm.reset();
  showCreatePreferenceModal.value = true;
};

const submitCreatePreferenceForm = () => {
  createPreferenceForm.post(route('admin.preferences.store', { category: props.category.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showCreatePreferenceModal.value = false;
      createPreferenceForm.reset();
    },
    onError: () => {
      // Errores ya se manejan mostrando props.errors
    }
  });
};

const openEditPreferenceModal = (preference) => {
  selectedPreference.value = preference; // Guardar la referencia
  editPreferenceForm.id = preference.id;
  editPreferenceForm.preference1 = preference.preference1;
  editPreferenceForm.preference2 = preference.preference2;
  showEditPreferenceModal.value = true;
};

const submitEditPreferenceForm = () => {
  if (!selectedPreference.value) return;
  editPreferenceForm.put(route('admin.preferences.update', { preference: selectedPreference.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showEditPreferenceModal.value = false;
      editPreferenceForm.reset();
      selectedPreference.value = null;
    },
    onError: () => {
      // Errores ya se manejan mostrando props.errors
    }
  });
};

const openDeletePreferenceModal = (preference) => {
  selectedPreference.value = preference;
  showDeletePreferenceModal.value = true;
};

const confirmDeletePreference = () => {
  if (!selectedPreference.value) return;
  useForm().delete(route('admin.preferences.delete', { preference: selectedPreference.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showDeletePreferenceModal.value = false;
      selectedPreference.value = null;
    },
    onError: (errors) => {
      // Manejar errores específicos de eliminación si es necesario
      console.error("Error deleting preference:", errors);
      alert("Error al eliminar la preferencia."); // Mensaje básico
      showDeletePreferenceModal.value = false; 
    }
  });
};

</script>

<template>
  <Head>
    <title>Detalles de Categoría: {{ category.name }}</title>
  </Head>

  <div class="min-h-screen bg-gray-100 pb-12"> <!-- Added padding-bottom -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">
          Categoría: {{ category.name }}
        </h1>
        <div class="flex items-center gap-4"> <!-- Wrapper for buttons -->
          <Link 
            :href="route('admin')"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition"
          >
            Volver al Panel
          </Link>
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
      </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Sección de Detalles de Categoría (Existente) -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Detalles de Categoría</h2>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
          <div class="sm:col-span-1">
            <dt class="text-sm font-medium text-gray-500">ID</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ category.id }}</dd>
          </div>
          <div class="sm:col-span-1">
            <dt class="text-sm font-medium text-gray-500">Nombre</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ category.name }}</dd>
          </div>
          <div class="sm:col-span-1">
            <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ new Date(category.created_at).toLocaleString() }}</dd>
          </div>
          <div class="sm:col-span-1">
            <dt class="text-sm font-medium text-gray-500">Última Actualización</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ new Date(category.updated_at).toLocaleString() }}</dd>
          </div>
        </dl>
      </div>

      <!-- Sección de Preferencias -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold text-gray-800">Preferencias de "{{ category.name }}"</h2>
          <button 
            @click="openCreatePreferenceModal"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition"
          >
            Crear Preferencia
          </button>
        </div>
        
        <!-- Tabla de Preferencias -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preferencia 1</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Votos</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preferencia 2</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Votos</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="preference in preferences" :key="preference.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ preference.id }}</td>
                <td class="px-6 py-4 whitespace-normal text-sm text-gray-900">{{ preference.preference1 }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ preference.preference1_votes_count ?? 0 }}</td>
                <td class="px-6 py-4 whitespace-normal text-sm text-gray-900">{{ preference.preference2 }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ preference.preference2_votes_count ?? 0 }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button 
                    @click="openEditPreferenceModal(preference)" 
                    class="text-indigo-600 hover:text-indigo-900 mr-3"
                  >
                    Editar
                  </button>
                  <button 
                    @click="openDeletePreferenceModal(preference)" 
                    class="text-red-600 hover:text-red-900"
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="!preferences || preferences.length === 0">
                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No hay preferencias en esta categoría.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Modals para Preferencias -->

    <!-- Modal Crear Preferencia -->
    <div v-if="showCreatePreferenceModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center z-50 px-4">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-lg w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Crear Nueva Preferencia en "{{ category.name }}"</h3>
        <form @submit.prevent="submitCreatePreferenceForm">
          <div class="mb-4">
            <label for="create-pref1" class="block text-sm font-medium text-gray-700">Opción 1</label>
            <input 
              v-model="createPreferenceForm.preference1" 
              type="text" 
              id="create-pref1" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
              required
            >
            <div v-if="createPreferenceForm.errors.preference1" class="text-red-500 text-sm mt-1">{{ createPreferenceForm.errors.preference1 }}</div>
          </div>
          <div class="mb-4">
            <label for="create-pref2" class="block text-sm font-medium text-gray-700">Opción 2</label>
            <input 
              v-model="createPreferenceForm.preference2" 
              type="text" 
              id="create-pref2" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
              required
            >
             <div v-if="createPreferenceForm.errors.preference2" class="text-red-500 text-sm mt-1">{{ createPreferenceForm.errors.preference2 }}</div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button 
              type="button" 
              @click="showCreatePreferenceModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button 
              type="submit"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
              :disabled="createPreferenceForm.processing"
            >
              Crear Preferencia
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Editar Preferencia -->
    <div v-if="showEditPreferenceModal && selectedPreference" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center z-50 px-4">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-lg w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Editar Preferencia (ID: {{ selectedPreference.id }})</h3>
        <form @submit.prevent="submitEditPreferenceForm">
          <div class="mb-4">
            <label for="edit-pref1" class="block text-sm font-medium text-gray-700">Opción 1</label>
            <input 
              v-model="editPreferenceForm.preference1" 
              type="text" 
              id="edit-pref1" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
              required
            >
             <div v-if="editPreferenceForm.errors.preference1" class="text-red-500 text-sm mt-1">{{ editPreferenceForm.errors.preference1 }}</div>
          </div>
          <div class="mb-4">
            <label for="edit-pref2" class="block text-sm font-medium text-gray-700">Opción 2</label>
            <input 
              v-model="editPreferenceForm.preference2" 
              type="text" 
              id="edit-pref2" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
              required
            >
             <div v-if="editPreferenceForm.errors.preference2" class="text-red-500 text-sm mt-1">{{ editPreferenceForm.errors.preference2 }}</div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button 
              type="button" 
              @click="showEditPreferenceModal = false; selectedPreference = null;"
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button 
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
              :disabled="editPreferenceForm.processing"
            >
              Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Confirmar Eliminación Preferencia -->
    <div v-if="showDeletePreferenceModal && selectedPreference" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center z-50 px-4">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmar Eliminación</h3>
        <p class="mb-4 text-gray-600">¿Estás seguro de que deseas eliminar la preferencia?</p>
        <p class="mb-2 text-sm text-gray-800 bg-gray-100 p-2 rounded">"{{ selectedPreference.preference1 }}" vs "{{ selectedPreference.preference2 }}"</p>
        <p class="mb-4 text-red-600 text-sm">Esta acción eliminará la preferencia y todos sus votos asociados. No se puede deshacer.</p>
        <div class="flex justify-end gap-3 mt-6">
          <button 
            type="button" 
            @click="showDeletePreferenceModal = false; selectedPreference = null;"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button 
            @click="confirmDeletePreference"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
          >
            Eliminar Preferencia
          </button>
        </div>
      </div>
    </div>

  </div>
</template> 