<template>
  <v-container class="fill-height p-0 mt-0">
    <!-- Table for traffic data -->
    <v-data-table
      :headers="trafficHeaders"
      :items="trafficData"
      item-key="timeInterval"
      class="elevation-5 mt-6"
      :max-width="{ xs: '100%', sm: '100%', md: '100%', lg: '800px' }"
    >
      <template v-slot:top>
        <v-toolbar flat>
          <v-toolbar-title>Traffic Data</v-toolbar-title>
        </v-toolbar>
      </template>
    </v-data-table>

    <!-- Table for search history -->
    <v-data-table
      :headers="headers"
      :items="searchHistory"
      item-key="id"
      class="elevation-5 mt-10"
      :max-width="{ xs: '100%', sm: '100%', md: '100%', lg: '800px' }"
    >
      <template v-slot:top>
        <v-toolbar flat>
          <v-toolbar-title>Search History</v-toolbar-title>
        </v-toolbar>
      </template>
    </v-data-table>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Headers for search history table
const headers = [
  { title: 'Country', key: 'country' },
  { title: 'City', key: 'city' },
  { title: 'Search Count', key: 'searchCount' }
];

// Headers for traffic table
const trafficHeaders = [
  { title: 'Time Interval', key: 'timeInterval' },
  { title: 'Visit Count', key: 'visitCount' }
];

// Data variables
const searchHistory = ref([]);
const trafficData = ref([]);

// Function to group traffic data by time intervals
function groupTrafficDataByTimeIntervals(data) {
  const intervals = [
    { start: '06:00', end: '15:00' },
    { start: '15:00', end: '21:00' },
    { start: '21:00', end: '24:00' },
    { start: '24:00', end: '06:00' }
  ];

  const groupedData = intervals.map(interval => {
    const { start, end } = interval;
    const visits = data.filter(entry => {
      const entryTime = entry.time.split(':');
      const entryHour = parseInt(entryTime[0]);
      const entryMinute = parseInt(entryTime[1]);
      const entryTimestamp = entryHour * 60 + entryMinute;

      const startTime = start.split(':');
      const startHour = parseInt(startTime[0]);
      const startMinute = parseInt(startTime[1]);
      const startTimestamp = startHour * 60 + startMinute;

      const endTime = end.split(':');
      const endHour = parseInt(endTime[0]);
      const endMinute = parseInt(endTime[1]);
      const endTimestamp = endHour * 60 + endMinute;

      if (startTimestamp <= entryTimestamp && entryTimestamp < endTimestamp) {
        return true;
      } else if (startTimestamp > endTimestamp && (entryTimestamp >= startTimestamp || entryTimestamp < endTimestamp)) {
        return true;
      }
      return false;
    });
    return { timeInterval: `${start} - ${end}`, visitCount: visits.length };
  });

  return groupedData;
}

// Fetch data on component mount
onMounted(async () => {
  try {
    // Fetch search history data
    const searchResponse = await axios.get('https://node79.webte.fei.stuba.sk/z4/api.php');
    const searchData = searchResponse.data;

    // Process search history data
    const searchCounts = {};
    searchData.forEach(({ country, city }) => {
      const key = `${country}-${city}`;
      searchCounts[key] = (searchCounts[key] || 0) + 1;
    });
    const searchHistoryData = Object.entries(searchCounts).map(([key, count]) => {
      const [country, city] = key.split('-');
      return { country, city, searchCount: count };
    });
    searchHistory.value = searchHistoryData;

    // Fetch traffic data
    const trafficResponse = await axios.get('https://node79.webte.fei.stuba.sk/z4/api.php?traffic=true');
    const rawTrafficData = trafficResponse.data;

    // Group traffic data by time intervals
    const groupedTrafficData = groupTrafficDataByTimeIntervals(rawTrafficData);
    trafficData.value = groupedTrafficData;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
});
</script>
