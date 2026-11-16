export type StoreId = "google" | "apple"

export type StoreLink = { store: StoreId; link: string }

export const storeBadges: Record<StoreId, { src: string; alt: string }> = {
  google: { src: "/badges/google-play.svg", alt: "Get it on Google Play" },
  apple: { src: "/badges/app-store.svg", alt: "Download on the App Store" },
}

export const fieldAppStores: StoreLink[] = [
  {
    store: "google",
    link: "https://play.google.com/store/apps/details?id=io.micropowermanager.fieldapp",
  },
  {
    store: "apple",
    link: "https://apps.apple.com/app/mpm-field-app/id6809758728",
  },
]
