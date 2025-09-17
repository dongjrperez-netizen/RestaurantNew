<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Upload, X, Eye, Image as ImageIcon } from 'lucide-vue-next';

interface Props {
  modelValue?: string | null;
  placeholder?: string;
  accept?: string;
  maxSize?: number; // in MB
  showPreview?: boolean;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Upload an image or enter URL',
  accept: 'image/*',
  maxSize: 5,
  showPreview: true,
  disabled: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  'upload': [file: File];
  'error': [message: string];
}>();

const fileInput = ref<HTMLInputElement>();
const isDragging = ref(false);
const isUploading = ref(false);
const urlInput = ref(props.modelValue || '');

// Watch for changes in modelValue to sync urlInput
watch(() => props.modelValue, (newValue) => {
  if (newValue !== null && newValue !== urlInput.value) {
    // Don't revoke blob URL immediately - let the new image load first
    urlInput.value = newValue;
  }
}, { immediate: true });

const hasImage = computed(() => {
  return props.modelValue && props.modelValue.length > 0;
});

const isUrl = computed(() => {
  return props.modelValue?.startsWith('http') || 
         props.modelValue?.startsWith('blob:') || 
         props.modelValue?.startsWith('/storage/');
});

const triggerFileSelect = () => {
  if (props.disabled) return;
  fileInput.value?.click();
};

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    processFile(file);
  }
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  isDragging.value = false;
  
  if (props.disabled) return;
  
  const files = event.dataTransfer?.files;
  if (files && files.length > 0) {
    processFile(files[0]);
  }
};

const processFile = (file: File) => {
  // Validate file type
  if (!file.type.startsWith('image/')) {
    emit('error', 'Please select a valid image file.');
    return;
  }

  // Validate file size
  if (file.size > props.maxSize * 1024 * 1024) {
    emit('error', `File size must be less than ${props.maxSize}MB.`);
    return;
  }

  isUploading.value = true;
  
  // Create preview URL immediately and store it
  const previewUrl = URL.createObjectURL(file);
  emit('update:modelValue', previewUrl);
  
  // Emit upload event for parent to handle
  emit('upload', file);
  
  // Don't automatically set uploading to false - let the parent handle it
};

const handleUrlChange = () => {
  emit('update:modelValue', urlInput.value || null);
};

const clearImage = () => {
  if (props.disabled) return;
  
  emit('update:modelValue', null);
  urlInput.value = '';
  
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  isDragging.value = true;
};

const handleDragLeave = () => {
  isDragging.value = false;
};

const handleImageLoad = () => {
  // Image loaded successfully - now we can clean up any old blob URL
  // Only revoke blob URLs when the new image has loaded successfully
  if (props.modelValue?.startsWith('/storage/') || props.modelValue?.startsWith('http')) {
    // Find and revoke any old blob URLs that might be hanging around
    const oldBlobUrl = urlInput.value;
    if (oldBlobUrl && oldBlobUrl.startsWith('blob:') && oldBlobUrl !== props.modelValue) {
      URL.revokeObjectURL(oldBlobUrl);
    }
  }
};

const handleImageLoadError = (event: Event) => {
  emit('error', 'Failed to load image');
};

// Expose methods for parent components
const resetUploadingState = () => {
  isUploading.value = false;
};

defineExpose({
  resetUploadingState
});
</script>

<template>
  <div class="space-y-4">
    <div class="space-y-2">
      <Label>Dish Image</Label>
      <div class="space-y-3">
        <!-- Image Preview -->
        <div v-if="hasImage && showPreview" class="relative">
          <Card class="overflow-hidden">
            <CardContent class="p-0">
              <div class="relative aspect-video bg-muted">
                <img
                  :key="modelValue"
                  :src="modelValue!"
                  alt="Dish preview"
                  class="w-full h-full object-cover"
                  @error="handleImageLoadError"
                  @load="handleImageLoad"
                />
                <div class="absolute inset-0 bg-black/40 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center">
                  <div class="flex gap-2">
                    <Button
                      type="button"
                      size="sm"
                      variant="secondary"
                      @click="triggerFileSelect"
                      :disabled="disabled"
                    >
                      <Upload class="w-4 h-4 mr-1" />
                      Replace
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      variant="destructive"
                      @click="clearImage"
                      :disabled="disabled"
                    >
                      <X class="w-4 h-4 mr-1" />
                      Remove
                    </Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Upload Area -->
        <div
          v-if="!hasImage || !showPreview"
          :class="[
            'relative border-2 border-dashed rounded-lg p-8 transition-colors cursor-pointer',
            isDragging
              ? 'border-primary bg-primary/5'
              : 'border-muted-foreground/25 hover:border-primary/50',
            disabled && 'opacity-50 cursor-not-allowed'
          ]"
          @click="triggerFileSelect"
          @drop="handleDrop"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
        >
          <input
            ref="fileInput"
            type="file"
            :accept="accept"
            class="hidden"
            @change="handleFileSelect"
            :disabled="disabled"
          />
          
          <div class="text-center">
            <div class="mb-4">
              <Upload 
                :class="[
                  'mx-auto h-12 w-12',
                  isUploading ? 'animate-bounce text-primary' : 'text-muted-foreground'
                ]" 
              />
            </div>
            <div class="space-y-2">
              <p class="text-sm font-medium">
                {{ isUploading ? 'Uploading...' : 'Drop image here or click to upload' }}
              </p>
              <p class="text-xs text-muted-foreground">
                Supports JPG, PNG, GIF up to {{ maxSize }}MB
              </p>
            </div>
          </div>
        </div>

        <!-- URL Input Alternative -->
        <div class="space-y-2">
          <Label class="text-sm text-muted-foreground">Or enter image URL:</Label>
          <div class="flex gap-2">
            <Input
              v-model="urlInput"
              placeholder="https://example.com/image.jpg"
              @blur="handleUrlChange"
              @keyup.enter="handleUrlChange"
              :disabled="disabled"
            />
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="handleUrlChange"
              :disabled="disabled"
            >
              <Eye class="w-4 h-4" />
            </Button>
          </div>
        </div>

        <!-- File Info -->
        <div v-if="hasImage && !isUrl" class="flex items-center gap-2 text-xs text-muted-foreground">
          <ImageIcon class="w-3 h-3" />
          <span>Uploaded image ready</span>
        </div>
        
        <!-- Upload Status -->
        <div v-if="isUploading" class="flex items-center gap-2 text-xs text-blue-600">
          <div class="animate-spin rounded-full h-3 w-3 border-2 border-blue-600 border-t-transparent"></div>
          <span>Uploading...</span>
        </div>
      </div>
    </div>
  </div>
</template>