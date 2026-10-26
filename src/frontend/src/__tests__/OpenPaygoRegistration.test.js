import fs from "fs"
import path from "path"
import vm from "vm"

import VeeValidate from "vee-validate"
import Vue from "vue"

import { token } from "../mixins/token.js"
import DeviceRepository from "../repositories/DeviceRepository.js"
import { DeviceService } from "../services/DeviceService.js"

jest.mock("../repositories/DeviceRepository.js", () => ({ list: jest.fn() }))

const componentSource = fs.readFileSync(
  path.join(
    __dirname,
    "../modules/SolarHomeSystem/OpenPaygoRegistrationModal.vue",
  ),
  "utf8",
)
const componentScript = componentSource
  .match(/<script>([\s\S]*?)<\/script>/)[1]
  .replace(/^import .*\n/gm, "")
  .replace("export default", "module.exports =")
const componentContext = {
  module: { exports: {} },
  notify: {},
  ApplianceService: function () {},
  SolarHomeSystemService: function () {},
  CustomerSearchSelect: {},
}
vm.runInNewContext(componentScript, componentContext)
const registrationModal = componentContext.module.exports

function operatorForm() {
  const form = {
    ...registrationModal.methods,
    $tc: (key) => key,
    $emit: jest.fn(),
    alertNotify: jest.fn(),
    loading: false,
    registration: {
      serialNumber: "MPMREGISTER01",
      manufacturerId: 4,
      applianceId: 5,
      personId: 261,
      secretKeyHex: "0123456789abcdef0123456789abcdef",
      startingCode: "728",
      nextCounter: "0",
      timeDivider: "1",
    },
    solarHomeSystemService: {
      registerOpenPaygoDevice: jest.fn().mockResolvedValue({ id: 1 }),
    },
  }
  return form
}

describe("OpenPAYGO registration", () => {
  it("keeps its field errors separate from MPM's global VeeValidate bag", () => {
    Vue.use(VeeValidate)
    const warnings = []
    const previousWarningHandler = Vue.config.warnHandler
    Vue.config.warnHandler = (message) => warnings.push(message)
    const form = new Vue(registrationModal)
    try {
      form.$tc = (key) => key
      expect(form.validateRegistration()).toBe(false)
      expect(form.validationErrors.serialNumber).toBeTruthy()
      expect(typeof form.errors.has).toBe("function")
      expect(warnings).toEqual([])
    } finally {
      form.$destroy()
      Vue.config.warnHandler = previousWarningHandler
    }
  })

  it("accepts a zero initial counter and converts provisioning numbers", async () => {
    const form = operatorForm()
    await form.register()
    expect(
      form.solarHomeSystemService.registerOpenPaygoDevice,
    ).toHaveBeenCalledWith(
      expect.objectContaining({
        startingCode: 728,
        nextCounter: 0,
        timeDivider: 1,
      }),
    )
  })

  it.each([
    ["serialNumber", "short"],
    ["personId", null],
    ["manufacturerId", null],
    ["applianceId", null],
    ["secretKeyHex", "not-a-device-key"],
    ["startingCode", "1000000000"],
    ["startingCode", "   "],
    ["nextCounter", "-1"],
    ["nextCounter", "100001"],
    ["nextCounter", null],
    ["timeDivider", "0"],
    ["timeDivider", "256"],
    ["timeDivider", "1.5"],
  ])("blocks invalid %s (%s) before sending the key", async (field, value) => {
    const form = operatorForm()
    form.registration[field] = value
    await form.register()
    expect(form.validationErrors[field]).toBeTruthy()
    expect(
      form.solarHomeSystemService.registerOpenPaygoDevice,
    ).not.toHaveBeenCalled()
  })

  it("clears the secret before notifying the parent of successful registration", async () => {
    const form = operatorForm()
    form.$emit.mockImplementation(() => {
      expect(form.registration.secretKeyHex).toBe("")
    })
    await form.register()
    expect(form.$emit).toHaveBeenCalledWith("created", { id: 1 })
    expect(form.loading).toBe(false)
  })

  it.each(["cancel", "external hide", "destroy"])(
    "clears the secret on %s",
    (closePath) => {
      const form = operatorForm()
      if (closePath === "cancel") form.close()
      if (closePath === "external hide")
        registrationModal.watch.show.call(form, false)
      if (closePath === "destroy") registrationModal.beforeDestroy.call(form)
      expect(form.registration.secretKeyHex).toBe("")
    },
  )

  it("shows a duplicate serial error and leaves the form available for correction", async () => {
    const form = operatorForm()
    form.solarHomeSystemService.registerOpenPaygoDevice.mockRejectedValue({
      message: "The serial number has already been taken.",
    })
    await form.register()
    expect(form.alertNotify).toHaveBeenCalledWith(
      "error",
      "The serial number has already been taken.",
    )
    expect(form.$emit).not.toHaveBeenCalled()
    expect(form.loading).toBe(false)
  })
})

describe("operator token copying", () => {
  beforeEach(() => {
    Object.defineProperty(navigator, "clipboard", {
      configurable: true,
      value: { writeText: jest.fn().mockResolvedValue() },
    })
  })

  function operator() {
    return { alertNotify: jest.fn(), $tc: (key) => key }
  }

  it("copies raw digits with a leading zero instead of display separators", async () => {
    const screen = operator()
    await token.methods.copyToken.call(screen, "024910173")
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith("024910173")
    expect(screen.alertNotify).toHaveBeenCalledWith(
      "success",
      "phrases.tokenCopied",
    )
  })

  it("explains clipboard denial without reporting success", async () => {
    const screen = operator()
    navigator.clipboard.writeText.mockRejectedValue(new Error("Denied"))
    await token.methods.copyToken.call(screen, "249101730")
    expect(screen.alertNotify).toHaveBeenCalledWith(
      "error",
      "phrases.tokenCopyFailed",
    )
  })
})

describe("customer device selection", () => {
  beforeEach(() => DeviceRepository.list.mockReset())

  it("requests unsold devices available to the selected customer", async () => {
    DeviceRepository.list.mockResolvedValue({
      status: 200,
      data: {
        data: [{ id: 1, device_serial: "MPMREGISTER01", person_id: 261 }],
      },
    })
    const service = new DeviceService()
    const devices = await service.getAvailableDevicesForAppliance(5, null, 261)
    expect(DeviceRepository.list).toHaveBeenCalledWith({
      appliance_id: 5,
      available_for_person_id: 261,
      per_page: 50,
    })
    expect(devices[0].deviceSerial).toBe("MPMREGISTER01")
  })

  it("preserves the server's explanation when selection is rejected", async () => {
    DeviceRepository.list.mockRejectedValue({
      response: { data: { message: "The selected customer is unavailable." } },
    })
    await expect(
      new DeviceService().getAvailableDevicesForAppliance(5, null, 261),
    ).rejects.toMatchObject({
      message: "The selected customer is unavailable.",
    })
  })

  it("reports a network failure without requiring an HTTP response", async () => {
    DeviceRepository.list.mockRejectedValue(new Error("Network Error"))
    await expect(
      new DeviceService().getAvailableDevicesForAppliance(5, null, 261),
    ).rejects.toMatchObject({ message: "Network Error" })
  })
})
