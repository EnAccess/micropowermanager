<template>
  <div class="md-layout md-gutter">
    <div class="md-layout-item md-size-100">
      <client-detail-card :person-id="personId" />
    </div>
    <div class="md-layout-item md-size-100">
      <sell-appliance-card :personId="personId" />
    </div>
  </div>
</template>

<script>
import ClientDetailCard from "../../../shared/ClientDetailCard"
import { PersonService } from "@/services/PersonService"
import SellApplianceCard from "./SellApplianceCard"
export default {
  name: "SellAppliance",
  components: { ClientDetailCard, SellApplianceCard },
  data() {
    return {
      person: {},
      personId: this.$route.params.id,
      personService: new PersonService(),
    }
  },
}
</script>

<style scoped></style>
