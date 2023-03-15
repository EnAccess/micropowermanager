import dotenv from "dotenv";
import express from "express";
import cors from "cors";
import { jetsonRouter } from "./router/JetsonRouter.js";
const app = express();

app.use(cors());
app.use(express.json());
app.use(
    express.urlencoded({
        extended: true,
    })
);
dotenv.config();

app.use("/api/jetson", jetsonRouter);
app.listen(3000, () => {
    console.log(
        "server up! PORT:" +
        3000 +
        "  Time:  " +
        new Date().toISOString().replace(/T/, " ").replace(/\..+/, "")
    );
});