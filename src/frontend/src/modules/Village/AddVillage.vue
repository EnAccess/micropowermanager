<template>
  <div>
    <widget :title="$tc('phrases.newVillage')" color="green">
      <md-card class="md-layout-item md-size-100">
        <md-card-content>
          <div class="md-layout md-gutter md-size-100">
            <div class="md-layout-item md-size-70 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.name')),
                }"
              >
                <label for="city_name">
                  {{ $tc("words.name") }}
                </label>
                <md-input
                  id="cityName"
                  :name="$tc('words.name')"
                  v-model="cityName"
                  v-validate="'required|min:3'"
                />
                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-30 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.miniGrid')),
                }"
              >
                <label for="miniGrid">
                  {{ $tc("words.miniGrid") }}
                </label>
                <md-select
                  v-model="selectedMiniGridId"
                  name="miniGrid"
                  id="miniGrid"
                  v-validate="'required'"
                >
                  <md-option
                    v-for="mg in miniGridService.list"
                    :value="mg.id"
                    :key="mg.id"
                  >
                    {{ mg.name }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first($tc("words.miniGrid")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-30 md-small-size-100">
              <md-field
                :class="{ 'md-invalid': errors.has($tc('words.country')) }"
              >
                <label for="country">Country</label>
                <md-select
                  v-model="selectedCountryId"
                  name="country"
                  id="country"
                  v-validate="'required'"
                >
                  <md-option value selected disabled>
                    -- {{ $tc("words.select") }} --
                  </md-option>
                  <md-option
                    v-for="country in countries"
                    :value="country.id"
                    :key="country.id"
                  >
                    {{ country.country_name }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first($tc("words.country")) }}
                </span>
              </md-field>
            </div>
          </div>

          <div class="md-layout md-gutter md-size-100">
            <div
              class="md-layout md-gutter md-size-60 md-small-size-100"
              style="padding-left: 1.5rem !important"
            >
              <form
                class="md-layout md-gutter"
                @submit.prevent="setPoints"
                style="padding-left: 1.5rem !important"
              >
                <div class="md-layout-item md-size-30 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.latitude')),
                    }"
                  >
                    <label for="latitude">
                      {{ $tc("words.latitude") }}
                    </label>
                    <md-input
                      id="latitude"
                      :name="$tc('words.latitude')"
                      v-model="cityLatLng.lat"
                      step="any"
                      maxlength="8"
                      v-validate="'required|decimal:5|max:8'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.latitude")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-30 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.longitude')),
                    }"
                  >
                    <label for="longitude">
                      {{ $tc("words.longitude") }}
                    </label>
                    <md-input
                      id="longitude"
                      :name="$tc('words.longitude')"
                      v-model="cityLatLng.lon"
                      step="any"
                      maxlength="8"
                      v-validate="'required|decimal:5|max:8'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.longitude")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-40 md-small-size-100">
                  <md-button type="submit" class="md-primary set-button">
                    {{ $tc("phrases.setPoints") }}
                  </md-button>
                </div>
              </form>
            </div>

            <div class="md-layout-item md-size-40 md-small-size-100">
              <md-button class="md-primary save-button" @click="saveVillage">
                {{ $tc("words.save") }}
              </md-button>
            </div>
          </div>

          <div class="md-layout-item md-size-100 map-area">
            <VillageMap
              ref="villageMapRef"
              :mapping-service="mappingService"
              :marker="true"
              @locationSet="villageLocationSet"
            />
          </div>
        </md-card-content>
        <md-progress-bar
          md-mode="indeterminate"
          class="md-progress-bar"
          v-if="loading"
        />
      </md-card>
    </widget>
    <redirection-modal
      :redirection-url="redirectionUrl"
      :imperative-item="imperativeItem"
      :dialog-active="redirectDialogActive"
    />
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { MiniGridService } from "@/services/MiniGridService"
import { CityService } from "@/services/CityService"
import { MappingService } from "@/services/MappingService"
import { ClusterService } from "@/services/ClusterService"
import RedirectionModal from "@/shared/RedirectionModal"
import { notify } from "@/mixins/notify"
import { ICONS, MARKER_TYPE } from "@/services/MappingService"
import VillageMap from "@/modules/Map/VillageMap.vue"
import CountryService from "@/services/CountryService"

export default {
  name: "AddVillage",
  mixins: [notify],
  components: {
    VillageMap,
    Widget,
    RedirectionModal,
  },
  data() {
    return {
      clusterService: new ClusterService(),
      miniGridService: new MiniGridService(),
      mappingService: new MappingService(),
      redirectedMiniGridId: null,
      selectedMiniGridId: null,
      geoData: null,
      villageSaved: false,
      loading: false,
      lastVillage: null,
      cityName: null,
      cityIndex: 0,
      cityService: new CityService(),
      countryService: new CountryService(),
      countries: [],
      selectedCountryId: null,
      cityLatLng: {
        lat: null,
        lon: null,
      },
      redirectionUrl: "/locations/add-mini-grid",
      imperativeItem: "Mini-Grid",
      redirectDialogActive: false,
    }
  },
  created() {
    this.redirectedMiniGridId = this.$route.params.id
    this.mappingService.setConstantMarkerUrl(ICONS.MINI_GRID)
    this.mappingService.setMarkerUrl(ICONS.VILLAGE)
    this.getCountries()
  },
  mounted() {
    this.setMiniGridOfVillage()
  },

  methods: {
    async setMiniGridOfVillage() {
      try {
        if (this.redirectedMiniGridId) {
          this.selectedMiniGridId = this.redirectedMiniGridId
          return
        }
        await this.getMiniGrids()

        if (this.miniGridService.list.length) {
          const selectedMiniGrid =
            this.miniGridService.list[this.miniGridService.list.length - 1]
          this.selectedMiniGridId = selectedMiniGrid.id
        } else {
          this.redirectDialogActive = true
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getMiniGrids() {
      try {
        await this.miniGridService.getMiniGrids()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getMiniGridWithGeoData(miniGridId) {
      try {
        return await this.miniGridService.getMiniGridGeoData(miniGridId)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getClusterGeoData(clusterId) {
      try {
        this.clusterId = clusterId
        return await this.clusterService.getClusterGeoLocation(clusterId)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getCountries() {
      try {
        await this.countryService.getCountries()
        this.countries = this.countryService.list
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveVillage() {
      const validator = await this.$validator.validateAll()
      if (validator) {
        if (!this.selectedCountryId) {
          this.alertNotify("error", this.$tc("phrases.selectCountry"))
          return
        }
        try {
          this.loading = true
          const city = {
            name: this.cityName,
            clusterId: this.clusterId,
            miniGridId: this.selectedMiniGridId,
            countryId: this.selectedCountryId,
            points: `${this.cityLatLng.lat},${this.cityLatLng.lon}`,
          }
          await this.cityService.createCity(city)
          this.alertNotify("success", this.$tc("phrases.newVillageNotify", 1))
          this.loading = false
          await this.$router.replace(
            `/dashboards/mini-grid/${this.selectedMiniGridId}`,
          )
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    villageLocationSet(data) {
      if (!data.error) {
        this.cityLatLng.lat = Number(
          data.geoDataItem.coordinates.lat.toFixed(5),
        )
        this.cityLatLng.lon = Number(
          data.geoDataItem.coordinates.lng.toFixed(5),
        )
      } else {
        this.cityLatLng.lat = null
        this.cityLatLng.lon = null
        this.$swal({
          type: "warning",
          text: data.error,
        })
      }
    },
    setPoints() {
      const location = [this.cityLatLng.lat, this.cityLatLng.lon]
      this.$refs.villageMapRef.setVillageMarkerManually(location)
    },
  },
  watch: {
    async selectedMiniGridId() {
      const markingInfos = []
      const miniGridWithGeoData = await this.getMiniGridWithGeoData(
        this.selectedMiniGridId,
      )
      const points = miniGridWithGeoData.location.points.split(",")
      if (points.length !== 2) {
        this.alertNotify("error", "Mini-Grid has no location")
        return
      }
      const lat = parseFloat(points[0])
      const lon = parseFloat(points[1])
      const clusterId = miniGridWithGeoData.cluster_id
      const clusterGeoData = await this.getClusterGeoData(clusterId)
      this.mappingService.setCenter([clusterGeoData.lat, clusterGeoData.lon])
      this.mappingService.setGeoData(clusterGeoData)
      markingInfos.push({
        id: miniGridWithGeoData.id,
        name: miniGridWithGeoData.name,
        serialNumber: null,
        lat: lat,
        lon: lon,
        deviceType: null,
        markerType: MARKER_TYPE.MINI_GRID,
      })
      this.mappingService.setMarkingInfos(markingInfos)
      this.$refs.villageMapRef.drawCluster()
      this.$refs.villageMapRef.setMiniGridMarker()
    },
  },
}
</script>

<style lang="scss" scoped>
.md-progress-bar {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
}

.save-button {
  background-color: #325932 !important;
  color: #fefefe !important;
  top: 0.5rem;
  float: right;
}

.set-button {
  background-color: #448aff !important;
  color: #fefefe !important;
  top: 0.5rem;
  float: left;
}

.map-area {
  z-index: 1 !important;
}
</style>
