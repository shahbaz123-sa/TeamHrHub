<script setup>
import { computed, onMounted, ref, watch } from 'vue'

definePage({
  meta: {
    layout: 'default',
    requiresAuth: true,
    public: false,
  },
})

// Reactive state
const userData = useCookie('userData')
const accessToken = useCookie('accessToken')
const isLoading = ref(true)
const isUpdating = ref(false)
const showPasswordDialog = ref(false)
const showImageUploadDialog = ref(false)

// User profile data
const userProfile = ref({
  id: null,
  name: '',
  email: '',
  avatar: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  country: '',
  postal_code: '',
})

// Password form
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})

// Image upload
const selectedFile = ref(null)
const previewImage = ref('')

// Form validation rules
const nameRules = [
  v => !!v || 'Name is required',
  v => v.length >= 2 || 'Name must be at least 2 characters',
]

const emailRules = [
  v => !!v || 'Email is required',
  v => /.+@.+\..+/.test(v) || 'Email must be valid',
]

const passwordRules = [
  v => !!v || 'Password is required',
  v => v.length >= 8 || 'Password must be at least 8 characters',
]

// Computed properties
const avatarUrl = computed(() => {
  if (previewImage.value) return previewImage.value
  if (userProfile.value.avatar) {
    // If it's already a full URL, return as is
    if (userProfile.value.avatar.startsWith('http')) {
      return userProfile.value.avatar
    }
    // If it's a storage path, construct the full URL
    if (userProfile.value.avatar.startsWith('avatars/')) {
      return `${window.location.origin}/storage/${userProfile.value.avatar}`
    }
    // If it's a relative path, add the origin
    return `${window.location.origin}${userProfile.value.avatar}`
  }
  return '/images/avatars/avatar-1.png'
})

const hasUserData = computed(() => (userData.value && userData.value.id) || accessToken.value)

// Methods
const fetchUserProfile = async () => {
  // If no access token, wait a bit and try again
  if (!accessToken.value) {
    setTimeout(async () => {
      if (accessToken.value) {
        await fetchUserProfile()
      } else {
        isLoading.value = false
      }
    }, 500)
    return
  }

  try {
    const response = await $api('/api/users/profile', {
      method: 'GET',
    })
    
    if (response.user) {
      userProfile.value = {
        id: response.user.id,
        name: response.user.name || '',
        email: response.user.email || '',
        avatar: response.user.avatar || '',
        phone: response.user.contact?.phone || '',
        address: response.user.contact?.address || '',
        city: response.user.contact?.city || '',
        state: response.user.contact?.state || '',
        country: response.user.contact?.country || '',
        postal_code: response.user.contact?.postal_code || '',
      }
    }
  } catch (error) {
    console.error('Failed to fetch user profile:', error)
    if (error.status === 401) {
      // Token expired or invalid, redirect to login
      $toast.error('Session expired. Please login again.')
      setTimeout(() => {
        window.location.href = '/login'
      }, 2000)
    } else {
      $toast.error('Failed to load profile data')
    }
  } finally {
    isLoading.value = false
  }
}

const updateProfile = async () => {
  if (!accessToken.value || !userProfile.value.id) return

  isUpdating.value = true
  try {
    const response = await $api('/api/users/profile', {
      method: 'POST',
      body: {
        name: userProfile.value.name,
        email: userProfile.value.email,
        phone: userProfile.value.phone,
        address: userProfile.value.address,
        city: userProfile.value.city,
        state: userProfile.value.state,
        country: userProfile.value.country,
        postal_code: userProfile.value.postal_code,
      },
    })

    if (response.user) {
      // Update userData cookie with new data
      const updatedUserData = { ...userData.value }
      updatedUserData.fullName = response.user.name
      updatedUserData.name = response.user.name
      updatedUserData.email = response.user.email
      userData.value = updatedUserData

      $toast.success('Profile updated successfully')
    }
  } catch (error) {
    console.error('Failed to update profile:', error)
    $toast.error('Failed to update profile')
  } finally {
    isUpdating.value = false
  }
}

const updatePassword = async () => {
  if (!accessToken.value || !userProfile.value.id) return

  isUpdating.value = true
  try {
    await $api('/api/users/profile', {
      method: 'POST',
      body: {
        current_password: passwordForm.value.current_password,
        password: passwordForm.value.password,
        password_confirmation: passwordForm.value.password_confirmation,
      },
    })

    $toast.success('Password updated successfully')
    showPasswordDialog.value = false
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }
  } catch (error) {
    console.error('Failed to update password:', error)
    $toast.error(error.data?.message || 'Failed to update password')
  } finally {
    isUpdating.value = false
  }
}

const onFileSelected = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Frontend validation for file type
    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg']
    const fileType = file.type.toLowerCase()
    
    if (!allowedTypes.includes(fileType)) {
      $toast.error('Only PNG, JPEG, and JPG files are allowed')
      // Clear the file input
      event.target.value = ''
      return
    }
    
    
    selectedFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      previewImage.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const uploadImage = async () => {
  if (!selectedFile.value || !userProfile.value.id) return

  isUpdating.value = true
  try {
    const formData = new FormData()
    formData.append('avatar', selectedFile.value)

    const response = await $api('/api/users/profile', {
      method: 'POST',
      body: formData,
    })

    if (response.user) {
      userProfile.value.avatar = response.user.avatar
      
      // Update userData cookie with new avatar
      const updatedUserData = { ...userData.value }
      updatedUserData.avatar = response.user.avatar
      userData.value = updatedUserData

      $toast.success('Profile image updated successfully');
      showImageUploadDialog.value = false
      selectedFile.value = null;
      previewImage.value = '';
      window.location.reload();
    }
  } catch (error) {
    
    let message = 'Failed to update profile image'
    
    // Handle different error response patterns
    if (error.response && error.response.status === 422) {
      // Validation errors - same pattern as employee creation
      message = Object.values(error.response?._data?.errors).slice(0, 2).join("\n")
    } else if (error.response && error.response._data?.error) {
      // Backend error message
      message = error.response._data.error
    } else if (error.response && error.response._data?.message) {
      // Backend general message
      message = error.response._data.message
    } else if (error.status === 401) {
      // Handle authentication errors
      $toast.error('Session expired. Please login again.')
      setTimeout(() => {
        window.location.href = '/login'
      }, 2000)
      return
    }
    
    $toast.error(message)
  } finally {
    isUpdating.value = false
  }
}

const cancelImageUpload = () => {
  showImageUploadDialog.value = false
  selectedFile.value = null
  previewImage.value = ''
}

// Lifecycle hooks
onMounted(async () => {
  // Wait for next tick to ensure cookies are loaded
  await nextTick()
  
  // Try to fetch profile data
  await fetchUserProfile()
  
  // Fallback: if still loading after 3 seconds, show error state
  setTimeout(() => {
    if (isLoading.value) {
      console.warn('Profile loading timeout - showing error state')
      isLoading.value = false
    }
  }, 3000)
})

// Watch for accessToken changes to handle refresh scenarios
watch(accessToken, (newToken) => {
  if (newToken && isLoading.value) {
    fetchUserProfile()
  }
}, { immediate: false })

// Watch for userData changes
watch(userData, (newUserData) => {
  if (newUserData && newUserData.id && isLoading.value) {
    fetchUserProfile()
  }
}, { deep: true, immediate: false })
</script>

<template>
  <div class="user-profile-page">
    <VContainer fluid>
      <!-- Loading State -->
      <div v-if="isLoading" class="d-flex justify-center align-center" style="min-block-size: 400px;">
        <VProgressCircular
          indeterminate
          size="64"
          color="primary"
        />
      </div>

      <!-- No User Data State -->
      <div v-else-if="!hasUserData" class="d-flex flex-column justify-center align-center" style="min-block-size: 400px;">
        <VIcon size="64" color="warning" class="mb-4">
          tabler-alert-triangle
        </VIcon>
        <h3 class="text-h5 mb-2">No User Data Available</h3>
        <p class="text-body-1 text-medium-emphasis mb-4">
          Please try refreshing the page or logging in again.
        </p>
        <VBtn color="primary" @click="() => window.location.reload()">
          Refresh Page
        </VBtn>
      </div>

      <!-- Profile Content -->
      <div v-else>
        <VRow>
          <!-- Profile Header -->
          <VCol cols="12">
            <VCard class="mb-6">
              <VCardText class="d-flex flex-column align-center pa-4 pa-md-8">
                <VAvatar
                  :size="$vuetify.display.xs ? 100 : 120"
                  class="mb-4 cursor-pointer"
                  @click="showImageUploadDialog = true"
                >
                  <VImg :src="avatarUrl" />
                  <VTooltip activator="parent" location="bottom">
                    Click to change profile image
                  </VTooltip>
                </VAvatar>
                
                <h2 class="text-h5 text-md-h4 mb-2 text-center">
                  {{ userProfile.name || 'User' }}
                </h2>
                <p class="text-body-2 text-md-body-1 text-medium-emphasis text-center mb-4">
                  {{ userProfile.email }}
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 gap-sm-4 w-100 justify-center">
                  <VBtn
                    color="primary"
                    prepend-icon="tabler-lock"
                    :size="$vuetify.display.xs ? 'small' : 'default'"
                    :block="$vuetify.display.xs"
                    @click="showPasswordDialog = true"
                  >
                    Change Password
                  </VBtn>
                  <VBtn
                    color="secondary"
                    prepend-icon="tabler-camera"
                    :size="$vuetify.display.xs ? 'small' : 'default'"
                    :block="$vuetify.display.xs"
                    @click="showImageUploadDialog = true"
                  >
                    Update Photo
                  </VBtn>
                </div>
              </VCardText>
            </VCard>
          </VCol>

        </VRow>
      </div>

      <!-- Password Change Dialog -->
      <VDialog v-model="showPasswordDialog" :max-width="$vuetify.display.xs ? '95vw' : '500'">
        <VCard>
          <VCardTitle class="pa-4 pa-md-6">
            <div class="d-flex align-center">
              <VIcon icon="tabler-lock" class="me-3" color="primary" />
              <h3 class="text-h6 text-md-h5">Change Password</h3>
            </div>
          </VCardTitle>
          
          <VCardText class="pa-4 pa-md-6">
            <VForm @submit.prevent="updatePassword">
              <VTextField
                v-model="passwordForm.current_password"
                label="Current Password"
                type="password"
                :rules="passwordRules"
                prepend-inner-icon="tabler-lock"
                variant="outlined"
                class="mb-4"
                required
              />
              
              <VTextField
                v-model="passwordForm.password"
                label="New Password"
                type="password"
                :rules="passwordRules"
                prepend-inner-icon="tabler-lock"
                variant="outlined"
                class="mb-4"
                required
              />
              
              <VTextField
                v-model="passwordForm.password_confirmation"
                label="Confirm New Password"
                type="password"
                :rules="[...passwordRules, v => v === passwordForm.password || 'Passwords must match']"
                prepend-inner-icon="tabler-lock"
                variant="outlined"
                class="mb-4"
                required
              />
              
              <div class="d-flex flex-column flex-sm-row justify-end gap-3 gap-sm-4">
                <VBtn
                  variant="outlined"
                  :block="$vuetify.display.xs"
                  @click="showPasswordDialog = false"
                >
                  Cancel
                </VBtn>
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="isUpdating"
                  :block="$vuetify.display.xs"
                >
                  Update Password
                </VBtn>
              </div>
            </VForm>
          </VCardText>
        </VCard>
      </VDialog>

      <!-- Image Upload Dialog -->
      <VDialog v-model="showImageUploadDialog" :max-width="$vuetify.display.xs ? '95vw' : '500'">
        <VCard>
          <VCardTitle class="pa-4 pa-md-6 pb-4">
            <div class="d-flex align-center">
              <VIcon icon="tabler-camera" class="me-3" color="primary" />
              <h3 class="text-h6 text-md-h5">Update Profile Image</h3>
            </div>
          </VCardTitle>
          
          <VCardText class="pa-4 pa-md-6">
            <div class="d-flex flex-column align-center">
              <!-- Profile Image Preview -->
              <div class="mb-4 mb-md-6">
                <VAvatar
                  :size="$vuetify.display.xs ? 120 : 150"
                  class="border-4 border-primary"
                >
                  <VImg :src="previewImage || avatarUrl" />
                </VAvatar>
              </div>
              
              <!-- Hidden File Input -->
              <input
                ref="fileInput"
                type="file"
                accept="image/png,image/jpeg,image/jpg"
                style="display: none;"
                @change="onFileSelected"
              />
              
              <!-- Upload Button -->
              <VBtn
                color="primary"
                variant="outlined"
                :size="$vuetify.display.xs ? 'small' : 'default'"
                prepend-icon="tabler-upload"
                class="mb-2"
                @click="$refs.fileInput.click()"
              >
                Choose New Image
              </VBtn>
              
              <!-- File Type Info -->
              <p class="text-caption text-medium-emphasis text-center mb-4">
                Only PNG, JPEG, and JPG files are allowed
              </p>
              
              <!-- File Info -->
              <div v-if="selectedFile" class="text-center mb-4">
                <VChip
                  color="success"
                  size="small"
                  prepend-icon="tabler-check"
                  class="text-wrap"
                >
                  {{ selectedFile.name }}
                </VChip>
              </div>
              
              <!-- Action Buttons -->
              <div class="d-flex flex-column flex-sm-row justify-end gap-3 gap-sm-4 w-100">
                <VBtn
                  variant="outlined"
                  :block="$vuetify.display.xs"
                  @click="cancelImageUpload"
                >
                  Cancel
                </VBtn>
                <VBtn
                  color="primary"
                  :loading="isUpdating"
                  :disabled="!selectedFile"
                  :block="$vuetify.display.xs"
                  prepend-icon="tabler-upload"
                  @click="uploadImage"
                >
                  Upload Image
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VDialog>
    </VContainer>
  </div>
</template>

<style scoped>
.user-profile-page {
  background-color: rgb(var(--v-theme-surface));
  min-block-size: 100vh;
}

.v-card {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 10%), 0 2px 4px -1px rgba(0, 0, 0, 6%);
}

.cursor-pointer {
  cursor: pointer;
}
</style>
