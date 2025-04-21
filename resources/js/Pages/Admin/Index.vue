<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';

// Recibir las categorías del controlador
const props = defineProps({
  categories: Array
});

// Reactive reference for categories, initialized with props
const localCategories = ref([...props.categories]); // Use spread to create a new array

// Watch for changes in props.categories and update localCategories
watch(() => props.categories, (newVal) => {
  localCategories.value = [...newVal]; // Update with a new array copy
}, { deep: true }); // Use deep watch just in case, although direct array replacement should trigger it

// Datos para el gráfico
const chartData = computed(() => {
  return {
    labels: localCategories.value.map(cat => cat.name),
    datasets: [{
      data: localCategories.value.map(cat => cat.total_votes),
      backgroundColor: generateColors(localCategories.value.length),
      borderWidth: 1
    }]
  };
});

// Opciones para el gráfico
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '60%', // Esto crea el efecto donut
  plugins: {
    legend: {
      position: 'right',
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const label = context.label || '';
          const value = context.raw || 0;
          const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
          const percentage = Math.round((value / total) * 100);
          return `${label}: ${value} votos (${percentage}%)`;
        }
      }
    }
  }
};

// Generar colores aleatorios pero visualmente agradables
function generateColors(count) {
  const colors = [];
  const hueStep = 360 / count;
  
  for (let i = 0; i < count; i++) {
    const hue = i * hueStep;
    colors.push(`hsl(${hue}, 70%, 60%)`);
  }
  
  return colors;
}

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

// Method to update category order
const updateCategoryOrder = () => {
  const orderedIds = localCategories.value.map((category, index) => ({
    id: category.id,
    position: index
  }));

  router.post(route('admin.categories.updateOrder'), {
    categories: orderedIds
  }, {
    preserveState: true, // Keep component state
    preserveScroll: true // Keep scroll position
    // Optional: Add onSuccess/onError handlers if needed
  });
};

// Referencia al elemento canvas del gráfico
let chartInstance = null;
const chartContainer = ref(null);

onMounted(() => {
  // Importar Chart.js dinámicamente para evitar problemas de SSR
  import('chart.js').then(module => {
    const { Chart, ArcElement, Tooltip, Legend, DoughnutController } = module;
    // Registrar los componentes necesarios
    Chart.register(ArcElement, Tooltip, Legend, DoughnutController);
    
    // Crear el gráfico
    if (chartContainer.value) {
      chartInstance = new Chart(chartContainer.value, {
        type: 'doughnut',
        data: chartData.value,
        options: chartOptions
      });
    }
  });
});

// Actualizar el gráfico cuando cambien los datos
watch(chartData, (newData) => {
  if (chartInstance) {
    chartInstance.data = newData;
    chartInstance.update();
  }
}, { deep: true });
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
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preferencias Totales</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Votos Totales</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <draggable 
                :list="localCategories" 
                item-key="id" 
                tag="tbody" 
                class="bg-white divide-y divide-gray-200"
                @end="updateCategoryOrder"
                handle=".drag-handle"
              >
                <template #item="{element: category}">
                  <tr :key="category.id">
                   <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <span class="drag-handle cursor-move mr-2">☰</span>
                      <Link 
                        :href="route('admin.categories.show', { category: category.id })" 
                        class="text-indigo-600 hover:text-indigo-900 hover:underline"
                      >
                        {{ category.name }}
                      </Link>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ category.preferences_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ category.total_votes }}
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
                </template>
              </draggable>
              <tbody v-if="!localCategories || localCategories.length === 0" class="bg-white divide-y divide-gray-200">
                <tr>
                  <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No hay categorías disponibles</td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Gráfico circular de votos por categoría -->
          <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Distribución de Votos por Categoría</h3>
            <div v-if="localCategories.length > 0" class="flex justify-center">
              <div class="w-full max-w-2xl h-80">
                <canvas ref="chartContainer"></canvas>
              </div>
            </div>
            <div v-else class="text-center text-gray-500 p-4">
              No hay datos de categorías para mostrar en el gráfico
            </div>
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
.drag-handle {
  cursor: move;
}

.ghost {
  opacity: 0.5;
  background: #c8ebfb;
}
</style> 