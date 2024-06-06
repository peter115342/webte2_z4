<template>
  <v-app>
    <!-- Navigation Drawer -->
    <v-navigation-drawer v-model="drawer" app temporary>
      <v-list>
        <v-list-item link @click="navigateTo('Home_view')">Trip Planning</v-list-item>
        <v-list-item link @click="navigateTo('User_view')">Website Traffic</v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar app color="primary">
      <v-toolbar-title>TravelForecast</v-toolbar-title>
      <v-btn text @click="navigateTo('Home_view')" v-if="!isSmallScreen">Trip Planning</v-btn>
      <v-btn text @click="navigateTo('User_view')" v-if="!isSmallScreen">Website Traffic</v-btn>
      <v-btn icon @click="drawer = !drawer" v-if="isSmallScreen">
        <v-icon>mdi-menu</v-icon>
      </v-btn>
    </v-app-bar>

    <!-- Main Content -->
    <v-main>
      <component :is="currentComponent" />
    </v-main>
  </v-app>
</template>

<script>
import { ref, watch } from 'vue';
import Home_view from './components/HelloWorld.vue';
import User_view from './components/UserData.vue';

export default {
  components: {
    Home_view,
    User_view,
  },
  setup() {
    const currentComponent = ref('Home_view');
    const drawer = ref(false);

    // Use a computed property to determine if it's a small screen
    const isSmallScreen = ref(window.innerWidth <= 600);

    // Watch for window resize to update isSmallScreen
    const handleResize = () => {
      isSmallScreen.value = window.innerWidth <= 600;
    };

    watch(isSmallScreen, (newValue) => {
      // Close the drawer when transitioning from small screen to large screen
      if (!newValue) drawer.value = false;
    });

    // Add window resize listener
    window.addEventListener('resize', handleResize);

    const navigateTo = (component) => {
      currentComponent.value = component;
      drawer.value = false; // Close drawer after navigating
    };

    // Function to count unique IP addresses and send to server
    const countUniqueIPAndSend = async () => {
      try {
        // Fetch client's IP address from api.ipify.org
        const ipResponse = await fetch('https://api.ipify.org?format=json');
        const ipData = await ipResponse.json();
        const clientIP = ipData.ip;

        // Send client's IP address to the server
        const response = await fetch('https://node79.webte.fei.stuba.sk/z4/api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ip: clientIP,
          }),
        });

        if (response.ok) {
          console.log('IP count updated successfully');
        } else {
          console.error('Failed to update IP count');
        }
      } catch (error) {
        console.error('Error:', error);
      }
    };

    // Call the function to count unique IP addresses and send to server
    countUniqueIPAndSend();

    return { currentComponent, drawer, isSmallScreen, navigateTo };
  },
};
</script>
