<template>
  <div>
    <md-dialog
      :md-active="internalDialogVisible"
      @update:mdActive="handleDialogActive"
      style="max-width: 60rem; margin: auto"
    >
      <md-dialog-title>Sell Appliance</md-dialog-title>
      <md-dialog-content
        style="overflow-y: auto"
        class="md-layout-item md-size-100"
      >
        <div v-if="loading">
          <loader />
        </div>
        <div v-else>
          <md-tabs>
            <md-tab
              id="count-based"
              @click="tabName = 'count-based'"
              md-label="Installment Count Based"
            >
              <form
                data-vv-scope="count-based-form"
                class="md-layout md-gutter"
              >
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.appliance'),
                    }"
                  >
                    <label for="appliance">
                      {{ $tc("words.appliance") }}
                    </label>
                    <md-select
                      name="appliance"
                      id="appliance"
                      v-model="selectedApplianceId"
                      v-validate="'required'"
                    >
                      <md-option disabled value>
                        --{{ $tc("words.select") }}--
                      </md-option>
                      <md-option
                        :value="appliance.id"
                        v-for="appliance in applianceService.list"
                        :key="appliance.id"
                      >
                        {{ appliance.name }}
                      </md-option>
                    </md-select>

                    <span class="md-error">
                      {{ errors.first("count-based-form.appliance") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.cost'),
                    }"
                  >
                    <label for="cost">
                      {{ $tc("words.cost") }}
                    </label>
                    <md-input
                      type="number"
                      name="cost"
                      id="cost"
                      v-model="applianceService.appliance.cost"
                      @keyup="checkDownPayment"
                      v-validate="'required|decimal'"
                    />
                    <span class="md-error">
                      {{ errors.first("count-based-form.cost") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.down_payment'),
                    }"
                  >
                    <label for="down_payment">Down Payment</label>
                    <md-input
                      type="number"
                      name="down_payment"
                      id="down_payment"
                      min="0"
                      v-model="applianceService.appliance.downPayment"
                      v-validate="'required|min_value:0|decimal'"
                      @keyup="checkDownPayment"
                    />
                    <span class="md-error">
                      {{ errors.first("count-based-form.down_payment") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.rate_type'),
                    }"
                  >
                    <label for="rate_type">
                      {{ $tc("phrases.rateType") }}
                    </label>
                    <md-select
                      name="rate_type"
                      id="rate_type"
                      v-model="applianceService.appliance.rateType"
                      v-validate="'required'"
                    >
                      <md-option value="weekly">
                        {{ $tc("words.week", 2) }}
                      </md-option>
                      <md-option value="monthly">
                        {{ $tc("words.month", 2) }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first("count-based-form.rate_type") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'count-based-form.installment_count',
                      ),
                    }"
                  >
                    <label for="rate">
                      {{ $tc("phrases.ratesCount") }}
                    </label>
                    <md-input
                      type="number"
                      name="installment_count"
                      id="installment_count"
                      v-model="applianceService.appliance.rate"
                      v-validate="'required|integer'"
                    />
                    <span class="md-error">
                      {{ errors.first("count-based-form.rate") }}
                    </span>
                  </md-field>
                </div>
              </form>
            </md-tab>
            <md-tab
              id="cost-based"
              @click="tabName = 'cost-based'"
              md-label="Installment Cost Based"
            >
              <form data-vv-scope="cost-based-form" class="md-layout md-gutter">
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.appliance'),
                    }"
                  >
                    <label for="appliance">
                      {{ $tc("words.appliance") }}
                    </label>
                    <md-select
                      name="appliance"
                      id="appliance"
                      v-model="selectedApplianceId"
                      v-validate="'required'"
                    >
                      <md-option disabled value>
                        --{{ $tc("words.select") }}--
                      </md-option>
                      <md-option
                        :value="appliance.id"
                        v-for="appliance in applianceService.list"
                        :key="appliance.id"
                      >
                        {{ appliance.name }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first("cost-based-form.appliance") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.cost'),
                    }"
                  >
                    <label for="cost">
                      {{ $tc("words.cost") }}
                    </label>
                    <md-input
                      type="number"
                      name="cost"
                      id="cost"
                      v-model="applianceService.appliance.cost"
                      @keyup="checkDownPayment"
                      v-validate="'required|decimal'"
                    />
                    <span class="md-error">
                      {{ errors.first("cost-based-form.cost") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.down_payment'),
                    }"
                  >
                    <label for="down_payment">Down Payment</label>
                    <md-input
                      type="number"
                      name="down_payment"
                      id="down_payment"
                      v-validate="'required|min_value:0|decimal'"
                      v-model="applianceService.appliance.downPayment"
                      min="0"
                      @keyup="checkDownPayment"
                    />
                    <span class="md-error">
                      {{ errors.first("cost-based-form.down_payment") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.rate_type'),
                    }"
                  >
                    <label for="rate_type">
                      {{ $tc("phrases.rateType") }}
                    </label>
                    <md-select
                      name="rate_type"
                      id="rate_type"
                      v-model="applianceService.appliance.rateType"
                      v-validate="'required'"
                    >
                      <md-option value="weekly">
                        {{ $tc("words.week", 2) }}
                      </md-option>
                      <md-option value="monthly">
                        {{ $tc("words.month", 2) }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first("cost-based-form.rate_type") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'cost-based-form.installment_cost',
                      ),
                    }"
                  >
                    <label for="installment_cost">
                      {{
                        $tc("words.rateCost", 1, {
                          rateType: applianceService.appliance.rateType,
                        })
                      }}
                    </label>
                    <md-input
                      type="number"
                      name="installment_cost"
                      id="installment_cost"
                      @keyup="calculateRateCountsOnRateCostChange"
                      v-model="applianceService.appliance.rateCost"
                      v-validate="'required|decimal'"
                    />
                    <span class="md-error">
                      {{ errors.first("cost-based-form.installment_cost") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-100 md-small-size-100">
                  <md-field>
                    <label for="minimumPayableAmount">
                      {{
                        applianceService.appliance.rateType === "weekly"
                          ? $tc("phrases.minimumPayableAmount", 1)
                          : $tc("phrases.minimumPayableAmount", 2)
                      }}
                    </label>
                    <md-input
                      type="number"
                      id="minimumPayableAmount"
                      name="minimumPayableAmount"
                      v-model="minimumPayableAmount"
                      readonly
                      disabled
                    />
                  </md-field>
                </div>
              </form>
            </md-tab>
          </md-tabs>
          <div class="md-layout-item md-size-100 md-small-size-100">
            <template v-if="isDeviceSelectionRequired">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('phrases.selectDevice')),
                }"
              >
                <label>
                  {{ $tc("phrases.selectDevice") }}
                </label>
                <md-select
                  :name="$tc('phrases.selectDevice')"
                  v-model="selectedDeviceSerial"
                  v-validate="'required'"
                >
                  <template v-if="!deviceSelectionList.length">
                    <md-option disabled>
                      <md-tooltip md-direction="top">
                        Consider changing the search term or create a suitable
                        device first.
                      </md-tooltip>
                      No available device found.
                    </md-option>
                  </template>
                  <template v-else>
                    <md-option
                      v-for="device in deviceSelectionList"
                      :key="device.id"
                      :value="device.serial"
                    >
                      {{ device.serial }}
                    </md-option>
                  </template>
                </md-select>
                <span class="md-error">
                  {{ errors.first($tc("phrases.selectDevice")) }}
                </span>
              </md-field>
            </template>
            <div class="coordinate-section">
              <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field>
                    <label for="device_latitude">
                      {{ $tc("words.latitude") }}
                    </label>
                    <md-input
                      id="device_latitude"
                      name="device_latitude"
                      :value="formattedDeviceLatitude"
                      readonly
                    />
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field>
                    <label for="device_longitude">
                      {{ $tc("words.longitude") }}
                    </label>
                    <md-input
                      id="device_longitude"
                      name="device_longitude"
                      :value="formattedDeviceLongitude"
                      readonly
                    />
                  </md-field>
                </div>
              </div>
              <md-button
                class="md-primary md-raised coordinate-button"
                type="button"
                @click="openLocationPicker"
              >
                Set Device Location
              </md-button>
              <p class="coordinate-hint">
                Device location defaults to the customer's primary address. Use
                the map to adjust these coordinates if needed.
              </p>
            </div>
          </div>
          <div v-if="applianceService.appliance.rate" style="padding: 1rem">
            <div
              style="
                font-size: 1rem;
                margin: 0;
                border-bottom: solid 1px #dedede;
              "
            >
              <div>
                Cost:
                {{ moneyFormat(applianceService.appliance.cost) }}
                <br />
              </div>
              <div style="margin-top: 10px">
                Down Payment :
                {{ moneyFormat(applianceService.appliance.downPayment) }}
                <br />
              </div>
              <div style="margin-top: 10px">
                Rates: {{ applianceService.appliance.rate }}
              </div>
            </div>
            <div v-if="showRates">
              <div
                v-for="x in parseInt(applianceService.appliance.rate)"
                :key="x"
              >
                <span v-if="x < 10" style="opacity: 0">0</span>
                {{ x }}&nbsp;-&nbsp;{{
                  moneyFormat(
                    getRate(
                      x,
                      applianceService.appliance.rate,
                      applianceService.appliance.cost -
                        applianceService.appliance.downPayment,
                    ),
                  )
                }}
              </div>
            </div>
          </div>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          v-if="showRatesButton"
          class="md-accent md-raised"
          @click="showRates = !showRates"
        >
          Show Rates Detail
        </md-button>
        <md-button
          type="button"
          class="md-primary md-raised"
          @click="saveAppliance"
        >
          {{ $tc("words.sell") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
    <md-dialog
      :md-active.sync="showLocationPicker"
      style="max-width: 70rem; margin: auto"
    >
      <md-dialog-title>Select Device Location</md-dialog-title>
      <md-dialog-content style="overflow-y: visible">
        <p class="coordinate-dialog-hint">
          Click on the map to place the device marker. Only one marker is
          allowed.
        </p>
        <DeviceLocationPickerMap
          v-if="showLocationPicker"
          :key="locationPickerKey"
          :mapping-service="mappingService"
          :map-container-id="locationPickerMapId"
          :marker="true"
          :marker-count="1"
          :initial-location="initialDeviceLocationArray"
          @location-selected="handleLocationSelected"
          @location-cleared="handleLocationCleared"
        />
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          class="md-primary md-raised"
          type="button"
          :disabled="!pendingDeviceLocation"
          @click="confirmDeviceLocation"
        >
          Use Location
        </md-button>
        <md-button type="button" @click="closeLocationPicker">
          {{ $tc("words.cancel") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { currency, notify } from "@/mixins"
import { ApplianceService } from "@/services/ApplianceService"
import { AssetPersonService } from "@/services/AssetPersonService"
import { DeviceService } from "@/services/DeviceService"
import { MappingService, ICONS } from "@/services/MappingService"
import { mapGetters } from "vuex"
import Loader from "@/shared/Loader.vue"
import DeviceLocationPickerMap from "./DeviceLocationPickerMap.vue"

const APPLIANCE_TYPE_SHS_ID = 1
const APPLIANCE_TYPE_E_BIKE_ID = 2
export default {
  name: "SellApplianceModal",
  mixins: [currency, notify],
  components: {
    Loader,
    DeviceLocationPickerMap,
  },
  props: {
    showSellApplianceModal: {
      required: true,
      type: Boolean,
    },
    person: {
      required: true,
    },
  },
  data() {
    return {
      applianceService: new ApplianceService(),
      assetPersonService: new AssetPersonService(),
      deviceService: new DeviceService(),
      mappingService: new MappingService(),
      selectedApplianceId: null,
      deviceSelectionList: [],
      isDeviceSelectionRequired: false,
      minimumPayableAmount: 0,
      selectedDeviceSerial: null,
      showRates: false,
      loading: false,
      tabName: "count-based",
      deviceLocation: null,
      pendingDeviceLocation: null,
      showLocationPicker: false,
      locationPickerKey: 0,
      locationPickerMapId: "",
      internalDialogVisible: false,
    }
  },
  created() {
    this.locationPickerMapId = `device-location-map-${this._uid}`
    this.internalDialogVisible = this.showSellApplianceModal
  },
  beforeMount() {
    this.getApplianceList()
    this.deviceService.getDevices()
    this.initializeDeviceLocation()
  },
  methods: {
    handleDialogActive(value) {
      this.internalDialogVisible = value
      if (!value) {
        this.$emit("hideModal")
      }
    },
    async getApplianceList() {
      try {
        await this.applianceService.getAppliances()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveAppliance() {
      const formName = `${this.tabName}-form`
      const validator = await this.$validator.validateAll(formName)
      if (!validator) return
      const appliance = this.applianceService.list.find(
        (x) => x.id === this.applianceService.appliance.id,
      )
      const isDeviceBindingRequired = this.isDeviceBindingRequired(appliance)
      if (isDeviceBindingRequired && !this.selectedDeviceSerial) {
        this.alertNotify("error", "Please select a device")
        return
      }
      const locationPoints = this.getGeoPointsForAppliance()
      if (!locationPoints) {
        this.alertNotify("error", "Please set the device location")
        return
      }
      this.$swal({
        type: "question",
        title: this.$tc("phrases.sellAsset", 0),
        text: this.$tc("phrases.sellAsset", 2, {
          cost: this.moneyFormat(this.applianceService.appliance.cost),
        }),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.sell"),
      }).then(async (result) => {
        if (result.value) {
          try {
            this.loading = true
            const points = locationPoints
            const soldApplianceParams = {
              id: this.applianceService.appliance.id,
              personId: this.person.id,
              ...this.applianceService.appliance,
              points: points,
              userId: this.user.id,
              deviceSerial: this.selectedDeviceSerial,
              address: this.getSelectedAddress(),
            }
            const soldAppliance =
              await this.assetPersonService.sellAppliance(soldApplianceParams)
            this.alertNotify("success", this.$tc("phrases.sellAsset", 1))
            await this.$router.push(
              "/sold-appliance-detail/" + soldAppliance.id,
            )
          } catch (e) {
            console.log(e)
            this.alertNotify("error", e.message)
          }
          this.loading = false
        }
      })
    },
    getGeoPointsForAppliance() {
      if (this.deviceCoordinatesAvailable) {
        return `${this.deviceLocation.lat},${this.deviceLocation.lon}`
      }
      const fallbackLocation = this.getFallbackLocationFromSelectedAddress()
      if (fallbackLocation) {
        const [lat, lon] = fallbackLocation
        return `${lat},${lon}`
      }
      return null
    },
    openLocationPicker() {
      if (!this.deviceLocation) {
        this.initializeDeviceLocation()
      }
      this.locationPickerKey += 1
      const fallbackLocation = this.getFallbackLocationFromSelectedAddress()
      const center = this.deviceCoordinatesAvailable
        ? [this.deviceLocation.lat, this.deviceLocation.lon]
        : fallbackLocation
      if (center) {
        this.mappingService.setCenter(center)
      }
      if (this.deviceCoordinatesAvailable) {
        this.pendingDeviceLocation = { ...this.deviceLocation }
      } else if (fallbackLocation) {
        this.pendingDeviceLocation = {
          lat: fallbackLocation[0],
          lon: fallbackLocation[1],
        }
      } else {
        this.pendingDeviceLocation = null
      }
      this.showLocationPicker = true
    },
    closeLocationPicker() {
      this.showLocationPicker = false
      this.pendingDeviceLocation = null
      this.locationPickerKey += 1
    },
    handleLocationSelected(location) {
      this.pendingDeviceLocation = location
    },
    handleLocationCleared() {
      this.pendingDeviceLocation = null
    },
    confirmDeviceLocation() {
      if (this.pendingDeviceLocation) {
        this.deviceLocation = {
          lat: this.pendingDeviceLocation.lat,
          lon: this.pendingDeviceLocation.lon,
        }
      } else {
        const fallbackLocation = this.getFallbackLocationFromSelectedAddress()
        this.deviceLocation = fallbackLocation
          ? {
              lat: fallbackLocation[0],
              lon: fallbackLocation[1],
            }
          : null
      }
      this.closeLocationPicker()
    },
    initializeDeviceLocation() {
      const fallbackLocation = this.getFallbackLocationFromSelectedAddress()
      if (fallbackLocation) {
        this.deviceLocation = {
          lat: fallbackLocation[0],
          lon: fallbackLocation[1],
        }
      } else {
        this.deviceLocation = null
      }
    },
    getSelectedAddress() {
      const addresses = this.person.addresses || []
      if (!addresses.length) return null
      const primaryAddress = addresses.find(
        (address) => address.isPrimary || address.is_primary,
      )
      return primaryAddress || addresses[0]
    },
    getFallbackLocationFromSelectedAddress() {
      const address = this.getSelectedAddress()
      if (!address) return null
      const addressPoints = this.parsePoints(address.geo?.points)
      if (addressPoints) return addressPoints
      const cityPoints = this.parsePoints(address.city?.location?.points)
      if (cityPoints) return cityPoints
      return null
    },
    resetDeviceSelectionValidation() {
      const fieldName = this.$tc("phrases.selectDevice")
      if (
        this.errors &&
        typeof this.errors.has === "function" &&
        this.errors.has(fieldName)
      ) {
        this.errors.remove(fieldName)
      }
      if (this.$validator && typeof this.$validator.reset === "function") {
        this.$validator.reset(fieldName)
      }
    },
    getDeviceSelectionList(appliance, availableDevices) {
      return availableDevices
        .filter((device) => {
          const assetId =
            device.device?.assetId ||
            device.device?.asset?.id ||
            device.assetId ||
            null
          if (!assetId) return false
          switch (appliance.assetTypeId) {
            case APPLIANCE_TYPE_SHS_ID:
              return (
                device.deviceType === "solar_home_system" &&
                assetId === this.selectedApplianceId
              )
            case APPLIANCE_TYPE_E_BIKE_ID:
              return (
                device.deviceType === "e_bike" &&
                assetId === this.selectedApplianceId
              )
            default:
              return assetId === this.selectedApplianceId
          }
        })
        .map((device) => {
          return {
            id: device.id,
            serial: device.deviceSerial || device.serial || device.serialNumber,
          }
        })
    },
    parsePoints(points) {
      if (!points || typeof points !== "string") return null
      const values = points.split(",").map((value) => value.trim())
      if (values.length !== 2) return null
      const lat = this.formatCoordinate(values[0], "lat")
      const lon = this.formatCoordinate(values[1], "lon")
      if (lat === null || lon === null) return null
      return [lat, lon]
    },
    formatCoordinate(value, type) {
      if (!this.isValidCoordinate(value, type)) return null
      const number = Number(value)
      return Number(number.toFixed(5))
    },
    isValidCoordinate(value, type) {
      if (value === null || value === undefined) return false
      const number = Number(value)
      if (!Number.isFinite(number)) return false
      if (type === "lat") {
        return number >= -90 && number <= 90
      }
      if (type === "lon") {
        return number >= -180 && number <= 180
      }
      return true
    },
    updateDeviceMarkerIcon(appliance) {
      if (!appliance) {
        this.mappingService.setMarkerUrl(ICONS.METER)
        return
      }
      switch (appliance.assetTypeId) {
        case APPLIANCE_TYPE_SHS_ID:
          this.mappingService.setMarkerUrl(ICONS.SHS)
          break
        case APPLIANCE_TYPE_E_BIKE_ID:
          this.mappingService.setMarkerUrl(ICONS.E_BIKE)
          break
        default:
          this.mappingService.setMarkerUrl(ICONS.METER)
      }
    },
    getRate(index, rateCount, cost) {
      if (index === parseInt(rateCount)) {
        return cost - (rateCount - 1) * Math.floor(cost / rateCount)
      } else {
        return Math.floor(cost / rateCount)
      }
    },
    checkDownPayment() {
      if (
        parseFloat(this.applianceService.appliance.downPayment) >
        parseFloat(this.applianceService.appliance.cost)
      ) {
        this.applianceService.appliance.downPayment = 0
        this.alertNotify(
          "warn",
          "Down Payment is not bigger than Appliance Cost",
        )
      } else if (
        this.applianceService.appliance.cost ===
        this.applianceService.appliance.downPayment
      ) {
        this.applianceService.appliance.rate = 0
      }
      this.minimumPayableAmount = 0
      this.rateCost = 0
    },
    calculateRateCountsOnRateCostChange() {
      const remainingCost =
        parseFloat(this.applianceService.appliance.cost) -
        parseFloat(this.applianceService.appliance.downPayment)
      const installmentCost = Number(this.applianceService.appliance.rateCost)
      if (installmentCost > remainingCost) {
        this.alertNotify(
          "warn",
          "Rate cost can not be bigger than remaining cost",
        )
        this.minimumPayableAmount = 0
        this.applianceService.appliance.rate = 0
        return
      }
      if (installmentCost < 1 || typeof installmentCost !== "number") {
        this.minimumPayableAmount = 0
        this.applianceService.appliance.rate = 0
        return
      }
      this.minimumPayableAmount = Math.floor(installmentCost)
      this.applianceService.appliance.rate = Math.floor(
        remainingCost / this.minimumPayableAmount,
      )
    },
    isDeviceBindingRequired(appliance) {
      if (!appliance) return false
      return (
        appliance.assetTypeId === APPLIANCE_TYPE_SHS_ID ||
        appliance.assetTypeId === APPLIANCE_TYPE_E_BIKE_ID
      )
    },
  },
  computed: {
    ...mapGetters({
      settings: "settings/getMainSettings",
      user: "auth/getAuthenticateUser",
    }),
    showRatesButton() {
      return this.applianceService.appliance.rate > 1
    },
    formattedDeviceLatitude() {
      if (!this.deviceLocation) return ""
      return this.deviceLocation.lat
    },
    formattedDeviceLongitude() {
      if (!this.deviceLocation) return ""
      return this.deviceLocation.lon
    },
    deviceCoordinatesAvailable() {
      return (
        this.deviceLocation &&
        this.isValidCoordinate(this.deviceLocation.lat, "lat") &&
        this.isValidCoordinate(this.deviceLocation.lon, "lon")
      )
    },
    initialDeviceLocationArray() {
      if (!this.deviceLocation) return null
      return [this.deviceLocation.lat, this.deviceLocation.lon]
    },
  },
  watch: {
    showSellApplianceModal(value) {
      this.internalDialogVisible = value
      if (value) {
        this.locationPickerKey += 1
      }
    },
    async selectedApplianceId() {
      this.applianceService.appliance.id = this.selectedApplianceId
      const availableDevices = this.deviceService.list.filter(
        (device) => !device.person,
      )
      const appliance = this.applianceService.list.find(
        (x) => x.id === this.applianceService.appliance.id,
      )
      this.updateDeviceMarkerIcon(appliance)
      if (this.isDeviceBindingRequired(appliance)) {
        this.isDeviceSelectionRequired = true
        this.deviceSelectionList = this.getDeviceSelectionList(
          appliance,
          availableDevices,
        )
        this.selectedDeviceSerial = null
        this.resetDeviceSelectionValidation()
      } else {
        this.isDeviceSelectionRequired = false
        this.deviceSelectionList = []
        this.selectedDeviceSerial = null
        this.resetDeviceSelectionValidation()
      }
      this.initializeDeviceLocation()
    },
  },
}
</script>

<style scoped>
.coordinate-section {
  margin-top: 1rem;
}

.coordinate-button {
  margin-top: 0.5rem;
}

.coordinate-hint {
  font-size: 0.875rem;
  color: #666;
  margin-top: 0.5rem;
}

.coordinate-dialog-hint {
  font-size: 0.875rem;
  color: #555;
  margin-bottom: 1rem;
}
</style>
