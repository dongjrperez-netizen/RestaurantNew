<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import { Plus, Edit, Trash2, GripVertical, Folder, Archive } from 'lucide-vue-next';

interface MenuCategory {
  category_id: number;
  category_name: string;
  sort_order: number;
  is_active: boolean;
  dishes?: Array<{
    dish_id: number;
    dish_name: string;
    status: string;
  }>;
  created_at: string;
  updated_at: string;
}

interface Props {
  categories: MenuCategory[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
  { title: 'Categories', href: '/menu-categories' },
];

// Form for creating/editing categories
const createForm = useForm({
  category_name: '',
  sort_order: 0,
});

const editForm = useForm({
  category_id: null as number | null,
  category_name: '',
  sort_order: 0,
  is_active: true,
});

// State
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const categoryToDelete = ref<MenuCategory | null>(null);

// Methods
const openEditDialog = (category: MenuCategory) => {
  editForm.category_id = category.category_id;
  editForm.category_name = category.category_name;
  editForm.sort_order = category.sort_order;
  editForm.is_active = category.is_active;
  showEditDialog.value = true;
};

const openDeleteDialog = (category: MenuCategory) => {
  categoryToDelete.value = category;
  showDeleteDialog.value = true;
};

const createCategory = () => {
  // Set sort order to be the next highest
  const maxOrder = Math.max(...props.categories.map(c => c.sort_order), 0);
  createForm.sort_order = maxOrder + 1;
  
  createForm.post('/menu-categories', {
    onSuccess: () => {
      showCreateDialog.value = false;
      createForm.reset();
    }
  });
};

const updateCategory = () => {
  if (!editForm.category_id) return;
  
  editForm.put(`/menu-categories/${editForm.category_id}`, {
    onSuccess: () => {
      showEditDialog.value = false;
      editForm.reset();
    }
  });
};

const deleteCategory = () => {
  if (!categoryToDelete.value) return;
  
  router.delete(`/menu-categories/${categoryToDelete.value.category_id}`, {
    onSuccess: () => {
      showDeleteDialog.value = false;
      categoryToDelete.value = null;
    }
  });
};

const getDishCountByStatus = (category: MenuCategory, status: string) => {
  if (!category.dishes) return 0;
  return category.dishes.filter(dish => dish.status === status).length;
};

const getActiveDishCount = (category: MenuCategory) => {
  return getDishCountByStatus(category, 'active');
};

const getTotalDishCount = (category: MenuCategory) => {
  return category.dishes?.length || 0;
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const reorderCategories = (dragIndex: number, hoverIndex: number) => {
  // This would implement drag & drop reordering
  // For now, we'll skip the implementation
  console.log('Reorder:', dragIndex, hoverIndex);
};
</script>

<template>
  <Head title="Menu Categories" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Menu Categories</h1>
          <p class="text-muted-foreground">Organize your menu items into categories</p>
        </div>
        
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger asChild>
            <Button>
              <Plus class="w-4 h-4 mr-2" />
              Add Category
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create New Category</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="createCategory" class="space-y-4">
              <div class="space-y-2">
                <Label for="create_category_name">Category Name *</Label>
                <Input
                  id="create_category_name"
                  v-model="createForm.category_name"
                  placeholder="e.g., Appetizers, Main Courses, Desserts"
                  :class="{ 'border-red-500': createForm.errors.category_name }"
                />
                <p v-if="createForm.errors.category_name" class="text-sm text-red-500">
                  {{ createForm.errors.category_name }}
                </p>
              </div>
              
              <div class="flex justify-end space-x-2">
                <Button type="button" variant="outline" @click="showCreateDialog = false">
                  Cancel
                </Button>
                <Button type="submit" :disabled="createForm.processing">
                  {{ createForm.processing ? 'Creating...' : 'Create Category' }}
                </Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Categories</CardTitle>
            <Folder class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ categories.length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Categories</CardTitle>
            <Folder class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ categories.filter(c => c.is_active).length }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Dishes</CardTitle>
            <Archive class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ categories.reduce((sum, c) => sum + getTotalDishCount(c), 0) }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Dishes</CardTitle>
            <Archive class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ categories.reduce((sum, c) => sum + getActiveDishCount(c), 0) }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Categories Table -->
      <Card>
        <CardHeader>
          <CardTitle>Categories List</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-12"></TableHead>
                <TableHead>Category Name</TableHead>
                <TableHead>Dishes</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Sort Order</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="category in categories" :key="category.category_id">
                <TableCell>
                  <GripVertical class="w-4 h-4 text-muted-foreground cursor-move" />
                </TableCell>
                <TableCell class="font-medium">
                  <div class="flex items-center gap-2">
                    <Folder class="w-4 h-4 text-muted-foreground" />
                    {{ category.category_name }}
                  </div>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <Badge variant="outline">
                      {{ getTotalDishCount(category) }} total
                    </Badge>
                    <Badge variant="default">
                      {{ getActiveDishCount(category) }} active
                    </Badge>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="category.is_active ? 'default' : 'secondary'">
                    {{ category.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">{{ category.sort_order }}</Badge>
                </TableCell>
                <TableCell class="text-sm text-muted-foreground">
                  {{ formatDate(category.created_at) }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end space-x-2">
                    <Button
                      variant="outline"
                      size="sm"
                      @click="openEditDialog(category)"
                    >
                      <Edit class="w-4 h-4" />
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      @click="openDeleteDialog(category)"
                      :disabled="getTotalDishCount(category) > 0"
                    >
                      <Trash2 class="w-4 h-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="categories.length === 0">
                <TableCell colspan="7" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No categories found</div>
                    <div class="text-sm">Get started by creating your first category.</div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Edit Dialog -->
      <Dialog v-model:open="showEditDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Category</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="updateCategory" class="space-y-4">
            <div class="space-y-2">
              <Label for="edit_category_name">Category Name *</Label>
              <Input
                id="edit_category_name"
                v-model="editForm.category_name"
                placeholder="Category name"
                :class="{ 'border-red-500': editForm.errors.category_name }"
              />
              <p v-if="editForm.errors.category_name" class="text-sm text-red-500">
                {{ editForm.errors.category_name }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="edit_sort_order">Sort Order</Label>
              <Input
                id="edit_sort_order"
                v-model="editForm.sort_order"
                type="number"
                min="0"
                placeholder="0"
              />
            </div>

            <div class="flex items-center space-x-2">
              <input
                id="edit_is_active"
                v-model="editForm.is_active"
                type="checkbox"
                class="rounded border-gray-300"
              />
              <Label for="edit_is_active">Active</Label>
            </div>
            
            <div class="flex justify-end space-x-2">
              <Button type="button" variant="outline" @click="showEditDialog = false">
                Cancel
              </Button>
              <Button type="submit" :disabled="editForm.processing">
                {{ editForm.processing ? 'Updating...' : 'Update Category' }}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Delete Confirmation Dialog -->
      <Dialog v-model:open="showDeleteDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="text-red-600">Delete Category</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <p>
              Are you sure you want to delete the category 
              <strong>"{{ categoryToDelete?.category_name }}"</strong>?
              This action cannot be undone.
            </p>
            <div v-if="categoryToDelete && getTotalDishCount(categoryToDelete) > 0" 
                 class="bg-yellow-50 p-3 rounded border border-yellow-200">
              <p class="text-sm text-yellow-800">
                <strong>Warning:</strong> This category contains {{ getTotalDishCount(categoryToDelete) }} dish(es). 
                You cannot delete a category that has dishes. Please move or delete all dishes first.
              </p>
            </div>
            <div class="flex justify-end space-x-2">
              <Button variant="outline" @click="showDeleteDialog = false">
                Cancel
              </Button>
              <Button 
                variant="destructive" 
                @click="deleteCategory"
                :disabled="categoryToDelete && getTotalDishCount(categoryToDelete) > 0"
              >
                Delete Category
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>