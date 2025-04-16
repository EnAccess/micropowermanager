<template>
  <section id="widget-grid">
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-55 md-small-size-100">
        <client-personal-data :person="person" v-if="isLoaded" />
        <addresses :person-id="person.id" v-if="person !== null" />
        <sms-history :person-id="personId" person-name="System" />
      </div>
      <div class="md-layout-item md-size-45 md-small-size-100">
        <payment-flow v-if="isLoaded" />
        <payment-detail v-if="isLoaded" />
      </div>
      <div class="md-layout-item md-size-100">
        <transactions :personId="personId" />
      </div>
      <div class="md-layout-item md-size-50 md-small-size-100">
        <div class="client-detail-card">
          <deferred-payments
            :person-id="person.id"
            v-if="person !== null"
            :person="person"
          />
        </div>
        <div class="client-detail-card">
          <ticket :personId="personId" />
        </div>
      </div>
      <div class="md-layout-item md-size-50 md-small-size-100">
        <div class="client-detail-card">
          <devices :devices="devices" v-if="isLoaded" />
        </div>
        <div class="client-detail-card">
          <widget :title="$tc('words.devices')" id="client-map">
            <client-map
              :mappingService="mappingService"
              ref="clientMapRef"
              :edit="true"
              @locationEdited="deviceLocationsEditedSet"
              :zoom="5"
            />
          </widget>
        </div>
      </div>
    </div>
  </section>
</template>
<script>
import PaymentFlow from "@/modules/Client/PaymentFlow"
import Transactions from "@/modules/Client/Transactions"
import PaymentDetail from "@/modules/Client/PaymentDetail"
import Ticket from "@/modules/Client/Ticket"
import Addresses from "@/modules/Client/Addresses"
import SmsHistory from "@/modules/Client/SmsHistory"
import ClientPersonalData from "@/modules/Client/ClientPersonalData"
import DeferredPayments from "@/modules/Client/DeferredPayments"
import ClientMap from "@/modules/Map/ClientMap.vue"
import { notify, timing } from "@/mixins"
import Devices from "@/modules/Client/Devices"
import Widget from "@/shared/Widget.vue"
import { PersonService } from "@/services/PersonService"
import { MappingService, MARKER_TYPE } from "@/services/MappingService"
import { DeviceAddressService } from "@/services/DeviceAddressService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "Client",
  mixins: [notify, timing],
  components: {
    DeferredPayments,
    ClientPersonalData,
    SmsHistory,
    PaymentFlow,
    Transactions,
    PaymentDetail,
    Ticket,
    Addresses,
    ClientMap,
    Devices,
    Widget,
  },
  data() {
    return {
      personService: new PersonService(),
      mappingService: new MappingService(),
      deviceAddressService: new DeviceAddressService(),
      personId: null,
      isLoaded: false,
      editPerson: false,
      person: null,
      devices: [],
    }
  },
  created() {
    this.personId = this.$route.params.id
    this.getDetails(this.personId)
  },
  destroyed() {
    this.$store.state.person = null
    this.$store.state.devices = null
  },
  mounted() {
    EventBus.$on("setMapCenterForDevice", (device) => {
      const points = device.address.geo.points.split(",")
      if (points.length !== 2) {
        this.alertNotify("warn", "Device has no location")
        return
      }
      const lat = parseFloat(points[0])
      const lon = parseFloat(points[1])
      this.$refs.clientMapRef.focusOnItem([lat, lon])
    })
  },
  methods: {
    async getDetails(id) {
      try {
        this.person = await this.personService.getPerson(id)
        this.isLoaded = true
        this.$store.state.person = this.person
        this.$store.state.devices = this.person.devices
        this.devices = this.person.devices
        this.setClientMapData()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async deviceLocationsEditedSet(editedItems) {
      try {
        await this.deviceAddressService.updateDeviceAddresses(editedItems)
        this.alertNotify("success", "Device locations updated successfully!")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    setClientMapData() {
      const markingInfos = []
      this.devices.map((device) => {
        const points = device.address.geo.points.split(",")
        if (points.length !== 2) {
          this.alertNotify("warn", "Device has no location")
          return
        }
        const lat = parseFloat(points[0])
        const lon = parseFloat(points[1])
        let markerType = ""
        switch (device.device_type) {
          case "e_bike":
            markerType = MARKER_TYPE.E_BIKE
            break
          case "solar_home_system":
            markerType = MARKER_TYPE.SHS
            break
          default:
            markerType = MARKER_TYPE.METER
        }
        markingInfos.push({
          id: device.id,
          name: device.name,
          serialNumber: device.device_serial,
          lat: lat,
          lon: lon,
          deviceType: device.device_type,
          markerType: markerType,
        })
        this.mappingService.setCenter([lat, lon])
      })
      this.mappingService.setMarkingInfos(markingInfos)
      this.$refs.clientMapRef.setDeviceMarkers()
    },
  },
}
</script>
<style>
[data-letters]:before {
  content: attr(data-letters);
  display: inline-block;
  font-size: 1em;
  width: 2.5em;
  height: 2.5em;
  line-height: 2.5em;
  text-align: center;
  border-radius: 50%;
  background: plum;
  vertical-align: middle;
  margin-right: 1em;
  color: white;
}
</style>
