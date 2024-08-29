<template>
  <div>
    <md-card>
      <div v-if="solar_data && solar_data.length > 0">
        Solar reading from ground :
        {{ this.solar_data }}
      </div>
      <div v-if="weather">
        <div
          style="position: absolute; left: 10px; font-size: 2rem; top: 0.5rem"
        >
          <img
            :src="
              'https://openweathermap.org/img/w/' +
              weather.weather[0].icon +
              '.png'
            "
            alt=""
          />
          {{ weather.main.temp }}&#8451;
          <br />
          <span
            style="
              font-size: 10px;
              position: absolute;
              bottom: -10px;
              left: 2rem;
            "
          >
            ({{ weather.weather[0].description }})
          </span>
        </div>

        <div
          style="position: absolute; right: 10px; top: 1rem; font-size: 0.8rem"
        >
          MAX {{ weather.main.temp_max }}&#8451;
        </div>

        <div
          style="
            position: absolute;
            right: 10px;
            top: 2.1rem;
            font-size: 0.8rem;
          "
        >
          MIN {{ weather.main.temp_min }}&#8451;
        </div>

        <div class="col-sm-12" style="min-height: 4rem"></div>
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-25">
            <div style="text-align: center; margin-bottom: 1px">
              <md-icon>wb_sunny</md-icon>
            </div>
            <div style="text-align: center">
              {{ weather.sys.sunrise | momento }}
            </div>
          </div>
          <div class="md-layout-item md-size-25">
            <div style="text-align: center">
              <md-icon>wb_sunny</md-icon>
            </div>
            <div style="text-align: center">
              {{ weather.sys.sunset | momento }}
            </div>
          </div>
          <div class="md-layout-item md-size-25">
            <div style="text-align: center">
              <md-icon>wb_sunny</md-icon>
            </div>
            <div style="text-align: center">{{ weather.wind.speed }} M/S</div>
          </div>
          <div
            class="md-layout-item md-size-25"
            style="
              position: absolute;
              bottom: 10px;
              right: 0;
              color: #818087;
              font-size: 11px;
            "
          >
            <md-icon>place</md-icon>
            {{ weather.name }}
          </div>
        </div>
      </div>
    </md-card>
  </div>
</template>

<script>
export default {
  name: "SolarDataAndWeather",
  data() {
    return {
      solar_data: null,
      weather: null,
    }
  },
  props: {
    mini_grid_id: {
      type: String,
      required: true,
    },
    mini_grid_coordinates: {
      type: String,
      required: true,
    },
  },
  created() {
    this.getSolarData()
    this.getWeatherData()
  },
  filters: {
    momento: function (date) {
      return new Date(date * 1000).toLocaleTimeString("en-GB").slice(0, 5)
    },
  },
  methods: {
    getSolarData() {
      axios
        .get(resources.solar.detail + this.mini_grid_id + "/solar")
        .then((response) => {
          this.solar_data = response.data.data
        })
    },
    getWeatherData() {
      let points = this.mini_grid_coordinates.split(",")

      let req = new Request(
        "https://api.openweathermap.org/data/2.5/weather?APPID=4a84b68e24abd9d99758a67f8d1d984b&units=metric&lat=" +
          points[0] +
          "&lon=" +
          points[1],
        {
          method: "GET",
        },
      )
      fetch(req)
        .then((response) => {
          return response.json()
        })
        .then((response) => {
          this.weather = response
        })
        .catch(function (e) {
          console.log(e)
        })
    },
  },
}
</script>

<style scoped></style>
