<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const step = ref(1);

const form = useForm({
  last_name: '',
  first_name: '',
  middle_name: '',
  date_of_birth: '',
  gender: '',
  email: '',
  phonenumber: '',
  password: '',
  password_confirmation: '',
  restaurant_name:'',
  address: '',
  postal_code:'',
  contact_number: '',
});

const nextStep = () => {
  if (step.value < 3) step.value++;
};
const prevStep = () => {
  if (step.value > 1) step.value--;
};

const submit = () => {
  form.postal_code = String(form.postal_code);
  form.phonenumber = String(form.phonenumber);
  form.contact_number = String(form.contact_number);
  
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),

  });
};
</script>

<template>
  <AuthBase title="Create an account" description="Enter your details below to create your account">
    <Head title="Register" />

    <form @submit.prevent="submit" class="flex flex-col gap-6">
      <div class="grid gap-6">
        <!-- STEP 1 -->
        <template v-if="step === 1">
          <div class="grid gap-2">
            <Label for="last_name">Last Name</Label>
            <Input id="last_name" type="text" required autofocus autocomplete="last_name" v-model="form.last_name" placeholder="Last name" />
            <InputError :message="form.errors.last_name" />
          </div>
          <div class="grid gap-2">
            <Label for="first_name">First Name</Label>
            <Input id="first_name" type="text" required autofocus autocomplete="first_name" v-model="form.first_name" placeholder="First name" />
            <InputError :message="form.errors.first_name" />
          </div>
             <div class="grid gap-2">
            <Label for="middle_name">Middle Name</Label>
            <Input id="middle_name" type="text" required autofocus autocomplete="middle_name" v-model="form.middle_name" placeholder="Middle name" />
            <InputError :message="form.errors.middle_name" />
          </div>

          <div class="grid gap-2">
            <Label for="date_of_birth">Date of Birth</Label>
            <Input 
              id="date_of_birth" 
              type="date" 
              required 
              autocomplete="bday" 
              v-model="form.date_of_birth" 
              placeholder="YYYY-MM-DD" 
            />
            <InputError :message="form.errors.date_of_birth" />
          </div>

          <div class="grid gap-2">
            <Label for="gender">Gender</Label>
            <select
              id="gender"
              v-model="form.gender"
              required
              class="border rounded p-2"
            >
              <option disabled value="">Select gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            <InputError :message="form.errors.gender" />
          </div>


          <div class="grid gap-2">
            <Label for="email">Email address</Label>
            <Input id="email" type="email" required autocomplete="email" v-model="form.email" placeholder="email@example.com" />
            <InputError :message="form.errors.email" />
          </div>

          <div class="grid gap-2">
            <Label for="phonenumber">Phone Number</Label>
            <Input id="phonenumber" type="text" required autocomplete="phonenumber" v-model="form.phonenumber" placeholder="Phone Number" />
            <InputError :message="form.errors.phonenumber" />
          </div>

          <Button type="button" class="mt-2 w-full" @click="nextStep">
            Next
          </Button>
        </template>

        <!-- STEP 2 -->
        <template v-else-if="step === 2">
          <div class="grid gap-2">
            <Label for="restaurant_name">Restaurant Name</Label>
            <Input id="restaurant_name" type="text" required autocomplete="restaurant_name" v-model="form.restaurant_name" placeholder="Restaurant Name" />
            <InputError :message="form.errors.restaurant_name" />
          </div>

           <div class="grid gap-2">
            <Label for="address">Address</Label>
            <Input id="address" type="text" required autocomplete="address" v-model="form.address" placeholder="Address" />
            <InputError :message="form.errors.address" />
          </div>
          <div class="grid gap-2">
            <Label for="postal_code">Postal Code</Label>
            <Input id="postal_code" type="text" required autocomplete="postal_code" v-model="form.postal_code" placeholder="Postal Code" />
            <InputError :message="form.errors.postal_code" />
          </div>


          <div class="grid gap-2">
            <Label for="contact_number">Contact Number</Label>
            <Input id="contact_number" type="text" required autocomplete="contact_number" v-model="form.contact_number" placeholder="Contact Number" />
            <InputError :message="form.errors.contact_number" />
          </div>


          <div class="flex gap-2">
            <Button type="button" class="w-1/2" @click="prevStep">
              Back
            </Button>
            <Button type="button" class="w-1/2" @click="nextStep"> 
              Next
            </Button>
          </div>
        </template>

        <!-- STEP 3 -->
        <template v-else>
          <div class="grid gap-2">
            <Label for="password">Password</Label>
            <Input
              id="password"
              type="password"
              required
              autocomplete="new-password"
              v-model="form.password"
              placeholder="Password"
            />
            <InputError :message="form.errors.password" />
          </div>

          <div class="grid gap-2">
            <Label for="password_confirmation">Confirm password</Label>
            <Input
              id="password_confirmation"
              type="password"
              required
              autocomplete="new-password"
              v-model="form.password_confirmation"
              placeholder="Confirm password"
            />
            <InputError :message="form.errors.password_confirmation" />
          </div>

          <div class="flex gap-2">
            <Button type="button" variant="secondary" class="w-1/2" @click="prevStep">
              Back
            </Button>
            <Button type="submit" class="w-1/2" :disabled="form.processing">
              <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
              Submit
            </Button>
          </div>
        </template>
      </div>


      <div class="text-center text-sm text-muted-foreground">
        Already have an account?
        <TextLink :href="route('login')" class="underline underline-offset-4">Log in</TextLink>
      </div>
    </form>
  </AuthBase>
</template>
