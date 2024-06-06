<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" sm="8" md="6">
        <v-text-field
          v-model="searchCity"
          label="Search for a City"
          dense
          outlined
          style="margin: 0 auto; margin-top: 12px;"
        ></v-text-field>
      </v-col>
    </v-row>
    <v-row justify="center" >
      <v-col cols="12" sm="10" md="8" lg="6" >
        <v-date-picker
          v-model="selectedDate"
          no-title
          scrollable
          :min="new Date()"
          color="primary"
          width="100%"
          class="elevation-5"
          style="margin: 0 auto; margin-bottom: 0px;"
        ></v-date-picker>
      </v-col>
    </v-row>
    <v-row justify="center" style="margin-top: 0px;">
      <v-col cols="12" sm="8" md="6" class="text-center">
        <v-btn color="primary" @click="getWeather" :disabled="!searchCity || !selectedDate"  class="elevation-5" style="padding: 1px; width: 65%; font-size: large; margin-top: 12px;">Get Weather</v-btn>
      </v-col>
    </v-row>
    <v-row justify="center" v-if="weatherData">
      <v-col cols="12" sm="10" md="8" lg="6" >
        <div class="weather-container" :style="{ background: getWeatherGradient(weatherData.current.current.condition.code), width: '100%', height: '100%' }">
          <h2>Weather for {{ capitalizedSearchCity }}</h2>
          <p v-if="weatherData.current">Current Temperature: {{ getCurrentTemperature() }}°C</p>
          <p v-if="weatherData.current.current.condition.code">Current Condition: <span class="emoji">{{ getWeatherEmoji(weatherData.current.current.condition.code) }}</span></p>
          <p v-if="selectedDate">Temperature for {{ formatDate(selectedDate) }}: {{ getTemperatureForSelectedDate() }}°C</p>
          <p v-if="selectedDate">Average Temperature for the month: {{ calculateAverageTemperatureForMonth() }}°C</p>
          <p v-if="weatherData.current.location">Capital: {{ capital }}</p>
          <p v-if="countryName">Country: {{ countryName }}</p>
          <p v-if="currency">Currency: {{ currency }}  {{ currency !== 'EUR' ? '(' + '1' + currencySymbol + ' = ' + currencyConverted + '€' + ')' : '' }} </p>
          <img v-if="flag" :src="flag" alt="Country Flag" style="width: 75px; height: auto; margin-top: 7px; border-radius: 5px">
        </div>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
  data() {
    return {
      searchCity: '',
      selectedDate: null,
      apiKey: 'dummy',
      weatherData: null,
      flag: null,
      countryName: '',
      currency: '',
      currencySymbol: '',
      currencyConverted: '',
      capital: '',
    };
  },
  computed: {
    capitalizedSearchCity() {
      return this.searchCity ? this.searchCity.charAt(0).toUpperCase() + this.searchCity.slice(1).toLowerCase() : '';
    },
    country() {
      if (this.weatherData && this.weatherData.current && this.weatherData.current.location) {
        return this.weatherData.current.location.country;
      }
      return '';
    },
  },
  methods: {
    async getWeather() {
      const currentDate = new Date();
      const selectedDate = new Date(this.selectedDate);
      const timeDifference = Math.abs(selectedDate - currentDate);
      const daysDifference = Math.ceil(timeDifference / (1000 * 60 * 60 * 24));

      let futureUrl = null;
      if (daysDifference >= 14) {
        futureUrl = `https://api.weatherapi.com/v1/future.json?key=${this.apiKey}&q=${this.searchCity}&dt=${this.formatDate(this.selectedDate)}`;
      }

      const forecastUrl = `https://api.weatherapi.com/v1/forecast.json?key=${this.apiKey}&q=${this.searchCity}&dt=${this.formatDate(this.selectedDate)}`;
      const currentUrl = `https://api.weatherapi.com/v1/current.json?key=${this.apiKey}&q=${this.searchCity}`;
      const monthUrl = `https://api.weatherapi.com/v1/history.json?key=${this.apiKey}&q=${this.searchCity}&dt=${this.formatDateForMean(this.selectedDate)}&end_dt=${this.formatLastDayOfMonth(this.selectedDate)}`;

      try {
        const [forecastResponse, currentResponse, monthResponse, futureResponse] = await Promise.all([
          fetch(forecastUrl),
          fetch(currentUrl),
          fetch(monthUrl),
          futureUrl ? fetch(futureUrl) : Promise.resolve(null)
        ]);

        const [forecastData, currentData, monthData, futureData] = await Promise.all([
          forecastResponse.json(),
          currentResponse.json(),
          monthResponse.json(),
          futureResponse ? futureResponse.json() : null
        ]);

        this.weatherData = {
          forecast: forecastData,
          current: currentData,
          month: monthData,
          future: futureData
        };

        console.log('Weather data:', this.weatherData);

        if (this.country) {
          await this.getCountryFlag(this.country); // Wait for getCountryFlag to finish
        }
        
        // Now that countryName is set, you can proceed to call the endpoint
        console.log(this.countryName);
        const response = await fetch('https://node79.webte.fei.stuba.sk/z4/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                country: this.countryName,
                city: this.capital,
            }),
        });

        const data = await response.json();
        console.log(data); // Handle response data as needed

      } catch (error) {
        console.error('Error fetching weather data:', error);
      }
    },

    getCurrentTemperature() {
      if (!this.weatherData || !this.weatherData.current) return null;
      return this.weatherData.current.current.temp_c;
    },

    getTemperatureForSelectedDate() {
      const currentDate = new Date();
      const selectedDate = new Date(this.selectedDate);
      const timeDifference = Math.abs(selectedDate - currentDate);
      const daysDifference = Math.ceil(timeDifference / (1000 * 60 * 60 * 24));

      if (daysDifference >= 14) {
        if (!this.weatherData.future.forecast.forecastday[0]) return null;
        return this.weatherData.future.forecast.forecastday[0].day.avgtemp_c;
      } else {
        if(!this.weatherData.forecast.forecast.forecastday[0]) return null;
        return this.weatherData.forecast.forecast.forecastday[0].day.avgtemp_c;
      }
    },

    calculateAverageTemperatureForMonth() {
      const date = new Date(this.selectedDate);
      const month = date.getMonth() + 1;
      const year = date.getFullYear();
      const currentDate = new Date();
      const currentMonth = currentDate.getMonth() + 1;
      const currentYear = currentDate.getFullYear();
      const currentDay = currentDate.getDate();

      const isCurrentMonth = month === currentMonth && year === currentYear;

      let daysInMonth = new Date(year, month, 0).getDate();

      if (isCurrentMonth) {
        daysInMonth = currentDay;
      }

      let totalTemperature = 0;

      for (let day = 1; day <= daysInMonth; day++) {
        const forecast = this.weatherData.month.forecast.forecastday[day - 1].day.avgtemp_c;
        if (forecast) {
          totalTemperature += forecast;
        }
      }

      return totalTemperature !== 0 ? (totalTemperature / daysInMonth).toFixed(2) : null;
    },

    formatDate(date) {
      const formattedDate = new Date(date);
      const year = formattedDate.getFullYear();
      const month = (formattedDate.getMonth() + 1).toString().padStart(2, '0');
      const day = formattedDate.getDate().toString().padStart(2, '0');
      return `${year}-${month}-${day}`;
    },

    formatDateForMean(date) {
      const currentDate = new Date();
      const currentMonth = currentDate.getMonth() + 1;
      const targetYear = date.getMonth() + 1 === currentMonth ? 2024 : 2023;

      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      return `${targetYear}-${month}-01`;
    },

    formatLastDayOfMonth(date) {
      const currentDate = new Date();
      const currentYear = currentDate.getFullYear();
      const currentMonth = currentDate.getMonth() + 1;

      let year = 2023;
      let month = date.getMonth() + 1;

      if (date.getMonth() === currentMonth - 1) {
        year = currentYear;
        month = currentMonth;
      }

      const lastDay = new Date(year, month, 0).getDate().toString().padStart(2, '0');
      return `${year}-${month.toString().padStart(2, '0')}-${lastDay}`;
    },

    async getCountryFlag(country) {
      try {
        // First, try fetching data using the name endpoint
        const nameResponse = await fetch(`https://restcountries.com/v3.1/name/${country}`);
        const nameData = await nameResponse.json();

        if (nameData && nameData.length > 0 && nameData[0].flags) {
          this.flag = nameData[0].flags.svg;
          this.countryName = nameData[0].name.common;
          this.currency = Object.keys(nameData[0].currencies)[0];
          this.currencySymbol = nameData[0].currencies[this.currency].symbol;
          this.capital = nameData[0].capital[0];

          if (this.currency !== 'EUR') {
            await this.convertToEUR();
          }
        } else {
          // If fetching using the name endpoint fails, try with translation
          const firstWord = country.split(' ')[0];
          const translationResponse = await fetch(`https://restcountries.com/v3.1/translation/${firstWord}`);
          const translationData = await translationResponse.json();

          if (translationData && translationData.length > 0 && translationData[0].flags) {
            this.flag = translationData[0].flags.svg;
            this.countryName = translationData[0].name.common;
            this.currency = Object.keys(translationData[0].currencies)[0];
            this.currencySymbol = translationData[0].currencies[this.currency].symbol;
            this.capital = translationData[0].capital[0];

            if (this.currency !== 'EUR') {
              await this.convertToEUR();
            }
          }
        }
      } catch (error) {
        console.error('Error fetching country flag:', error);
      }
    },

    async convertToEUR() {
      try {
        const response = await fetch(`https://api.freecurrencyapi.com/v1/latest?apikey=dummy&base_currency=EUR`);
        const data = await response.json();
        if (data && data.data && data.data[this.currency]) {
          const conversionRate = data.data[this.currency];
          if (conversionRate) {
            this.currencyConverted = (1 / conversionRate).toFixed(4);
          }
        }
      } catch (error) {
        console.error('Error converting currency:', error);
      }
    },

    getWeatherEmoji(weatherId) {
      switch (weatherId) {
        case 1000:
          return '☀️'; // Sunny
        case 1003:
          return '⛅'; // Partly cloudy
        case 1006:
          return '☁️'; // Cloudy
        case 1009:
          return '☁️'; // Overcast
        case 1030:
          return '🌫️'; // Mist
        case 1063:
        case 1180:
          return '🌧️'; // Patchy rain possible / Patchy light rain
        case 1066:
        case 1210:
          return '🌨️'; // Patchy snow possible / Patchy light snow
        case 1069:
          return '🌨️'; // Patchy sleet possible
        case 1072:
          return '🌧️'; // Patchy freezing drizzle possible
        case 1087:
          return '⛈️'; // Thundery outbreaks possible
        case 1114:
        case 1117:
          return '🌨️'; // Blowing snow / Blizzard
        case 1135:
          return '🌁'; // Fog
        case 1147:
          return '🌁'; // Freezing fog
        case 1150:
        case 1153:
        case 1183:
          return '🌧️'; // Patchy light drizzle / Light drizzle / Light rain
        case 1168:
        case 1171:
          return '🌧️'; // Freezing drizzle / Heavy freezing drizzle
        case 1186:
        case 1189:
        case 1192:
        case 1195:
          return '🌧️'; // Moderate rain at times / Moderate rain / Heavy rain at times / Heavy rain
        case 1198:
        case 1201:
          return '🌨️'; // Light freezing rain / Moderate or heavy freezing rain
        case 1204:
        case 1207:
          return '🌨️'; // Light sleet / Moderate or heavy sleet
        case 1213:
        case 1216:
        case 1219:
          return '🌨️'; // Light snow / Patchy moderate snow / Moderate snow
        case 1222:
        case 1225:
          return '🌨️'; // Patchy heavy snow / Heavy snow
        case 1237:
          return '🌨️'; // Ice pellets
        case 1240:
        case 1243:
        case 1246:
          return '🌧️'; // Light rain shower / Moderate or heavy rain shower / Torrential rain shower
        case 1249:
        case 1252:
          return '🌨️'; // Light sleet showers / Moderate or heavy sleet showers
        case 1255:
        case 1258:
          return '🌨️'; // Light snow showers / Moderate or heavy snow showers
        case 1261:
        case 1264:
          return '⛈️'; // Light showers of ice pellets / Moderate or heavy showers of ice pellets
        case 1273:
          return '⛈️'; // Patchy light rain with thunder
        case 1276:
          return '⛈️'; // Moderate or heavy rain with thunder
        case 1279:
          return '🌨️'; // Patchy light snow with thunder
        case 1282:
          return '🌨️'; // Moderate or heavy snow with thunder
        default:
          return '🤷'; // Unknown condition
      }
    },

    getWeatherGradient(weatherId) {
      switch (weatherId) {
        case 1000:
          return 'linear-gradient(to bottom, #ff8c00, #ffd700)'; // Sunny (Orange to Yellow)
        case 1003:
          return 'linear-gradient(to bottom, #87ceeb, #ffffff)'; // Partly cloudy (Light Blue to White)
        case 1006:
          return 'linear-gradient(to bottom, #708090, #d3d3d3)'; // Cloudy (Slate Gray to Light Gray)
        case 1009:
          return 'linear-gradient(to bottom, #708090, #d3d3d3)'; // Overcast (Slate Gray to Light Gray)
        case 1030:
          return 'linear-gradient(to bottom, #708090, #d3d3d3)'; // Mist (Slate Gray to Light Gray)
        case 1063:
        case 1180:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Patchy rain possible / Patchy light rain (Blue to Sky Blue)
        case 1066:
        case 1210:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Patchy snow possible / Patchy light snow (White to Alice Blue)
        case 1069:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Patchy sleet possible (White to Alice Blue)
        case 1072:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Patchy freezing drizzle possible (Blue to Sky Blue)
        case 1087:
          return 'linear-gradient(to bottom, #708090, #d3d3d3)'; // Thundery outbreaks possible (Slate Gray to Light Gray)
        case 1114:
        case 1117:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Blowing snow / Blizzard (White to Alice Blue)
        case 1135:
          return 'linear-gradient(to bottom, #708090, #d3d3d3)'; // Fog (Slate Gray to Light Gray)
        case 1147:
          return 'linear-gradient(to bottom, #708090, #d3d3d3)'; // Freezing fog (Slate Gray to Light Gray)
        case 1150:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Patchy light drizzle / Light drizzle / Light rain (Blue to Sky Blue)
        case 1168:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Freezing drizzle / Heavy freezing drizzle (Blue to Sky Blue)
        case 1186:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Moderate rain at times / Moderate rain / Heavy rain at times / Heavy rain (Blue to Sky Blue)
        case 1198:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Light freezing rain / Moderate or heavy freezing rain (White to Alice Blue)
        case 1204:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Light sleet / Moderate or heavy sleet (White to Alice Blue)
        case 1213:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Light snow / Patchy moderate snow / Moderate snow (White to Alice Blue)
        case 1222:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Patchy heavy snow / Heavy snow (White to Alice Blue)
        case 1237:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Ice pellets (White to Alice Blue)
        case 1240:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Light rain shower / Moderate or heavy rain shower / Torrential rain shower (Blue to Sky Blue)
        case 1249:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Light sleet showers / Moderate or heavy sleet showers (White to Alice Blue)
        case 1255:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Light snow showers / Moderate or heavy snow showers (White to Alice Blue)
        case 1261:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Light showers of ice pellets / Moderate or heavy showers of ice pellets (Blue to Sky Blue)
        case 1273:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Patchy light rain with thunder (Blue to Sky Blue)
        case 1276:
          return 'linear-gradient(to bottom, #1e90ff, #00bfff)'; // Moderate or heavy rain with thunder (Blue to Sky Blue)
        case 1279:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Patchy light snow with thunder (White to Alice Blue)
        case 1282:
          return 'linear-gradient(to bottom, #ffffff, #f0f8ff)'; // Moderate or heavy snow with thunder (White to Alice Blue)
        default:
          return 'linear-gradient(to bottom, #808080, #c0c0c0)'; // Unknown condition (Gray to Light Gray)
      }
    },

  },
};
</script>

<style>

.weather-container {
  padding: 20px;
  border-radius: 10px;
  color: rgb(29, 29, 29);
  font-size: larger;
}

.emoji {
  font-size: 26px;
}
</style>
