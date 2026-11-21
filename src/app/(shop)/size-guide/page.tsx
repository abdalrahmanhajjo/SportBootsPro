import type { Metadata } from "next";
import { InfoPage } from "@/components/shop/InfoPage";

export const metadata: Metadata = { title: "Size guide", description: "Convert EU, UK and US shoe sizes and measure your foot." };

const rows = [
  ["38", "5", "6", "24.0"],
  ["39", "6", "7", "24.5"],
  ["40", "6.5", "7.5", "25.0"],
  ["41", "7.5", "8.5", "26.0"],
  ["42", "8", "9", "26.5"],
  ["43", "9", "10", "27.5"],
  ["44", "9.5", "10.5", "28.0"],
];

export default function SizeGuidePage() {
  return (
    <InfoPage eyebrow="Help" title="Size guide" intro="Our sizes are EU sizes. Use this table to convert and check your foot length.">
      <div className="card mt-8 overflow-x-auto">
        <table className="table-base">
          <thead>
            <tr>
              <th scope="col">EU</th>
              <th scope="col">UK</th>
              <th scope="col">US (men)</th>
              <th scope="col">Foot length (cm)</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r[0]}>
                {r.map((c, i) => (
                  <td key={i} className={i === 0 ? "font-semibold" : ""}>
                    {c}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <h2>How to measure</h2>
      <ul>
        <li>Stand on a sheet of paper with your heel against a wall.</li>
        <li>Mark the tip of your longest toe and measure from the wall in centimetres.</li>
        <li>Measure both feet in the evening and use the larger number.</li>
        <li>Between two sizes? Choose the larger one, especially for running shoes.</li>
      </ul>
    </InfoPage>
  );
}
