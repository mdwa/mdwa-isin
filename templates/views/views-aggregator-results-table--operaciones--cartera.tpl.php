<?php
/**
 * @file
 * views-aggregator-results-table.tpl.php
 *
 * Template to display views results after aggregation as a table.
 *
 * This template is based on the one in the Views module:
 * views/themes/views-view-table.tpl.php
 *
 * - $title : The title of this group of rows. May be empty.
 * - $header: An array of header labels keyed by field id.
 * - $caption: The caption for this table. May be empty.
 * - $header_classes: An array of header classes keyed by field id.
 * - $fields: An array of CSS IDs to use for each field id.
 * - $classes: A class or classes to apply to the table, based on settings.
 * - $grouping_field_class: A class to apply to cells in the group aggregation column
 * - $totals_row_class: A class to apply to the column aggregation row
 * - $row_classes: An array of classes to apply to each row, indexed by row
 *   number. This matches the index in $rows.
 * - $rows: An array of row items. Each row is an array of content.
 *   $rows are keyed by row number, fields within rows are keyed by field ID.
 * - $field_classes: An array of classes to apply to each field, indexed by
 *   field id, then row number. This matches the index in $rows.
 * - $totals_row_position: whether to show the totals row at top, bottom or both
 * @ingroup views_templates
 */
?>
<table <?php if ($classes): ?>class="<?php print $classes; ?>"<?php endif ?><?php print $attributes; ?>>
  <?php if (!empty($title) || !empty($caption)) : ?>
    <caption><?php print $caption . $title; ?></caption>
  <?php endif; ?>
    <thead>
      <?php if (!empty($header)) : ?>
        <tr>
          <?php foreach ($header as $field => $label): 
            $hclasses = isset($header_classes[$field]) ? $header_classes[$field] : '';
            if ($field === $grouping_field) {
              $hclasses .= " $grouping_field_class";
            }
          ?>
            <th <?php if (!empty($hclasses)): ?>class="<?php print $hclasses; ?>"<?php endif ?>>
              <?php print $label; ?>
            </th>
          <?php endforeach ?>
        </tr>
      <?php endif; ?>
      <?php if (($totals_row_position & 1) && !empty($totals)) : ?>
        <tr <?php if (!empty($totals_row_class)): ?>class="<?php print $totals_row_class; ?>"<?php endif ?>>
          <?php
            // Use order of the row fields to output the totals likewise.
            foreach (array_keys(reset($rows)) as $field):
              $hclasses = isset($header_classes[$field]) ? $header_classes[$field] : '';
              if ($field === $grouping_field) {
                $hclasses .= " $grouping_field_class";
              }
          ?>
            <th <?php if (!empty($hclasses)): ?>class="<?php print $hclasses; ?>"<?php endif ?>>
              <?php print isset($totals[$field]) ? $totals[$field] : ''; ?>
            </th>
          <?php endforeach ?>
        </tr>
      <?php endif; ?>
    </thead>
    <tbody>
      <?php foreach ($rows as $r => $row): ?>
        <tr <?php if (!empty($row_classes[$r])): ?>class="<?php print implode(' ', $row_classes[$r]); ?>"<?php endif ?>>
          <?php foreach ($row as $field => $content): 
            $td_class = empty($field_classes[$field][$r]) ? '' : $field_classes[$field][$r];
            if ($field === $grouping_field) {
              $td_class .= " $grouping_field_class";
            }
          ?>
            <td <?php if (!empty($td_class)): ?>class="<?php print $td_class; ?>"<?php endif ?>
                <?php if (!empty($field_attributes[$field][$r])): ?><?php print drupal_attributes($field_attributes[$field][$r]); ?><?php endif ?>>
              <?php print $content; ?>
            </td>
          <?php endforeach ?>
        </tr>
      <?php endforeach ?>
    </tbody>
  <?php if (($totals_row_position & 2) && !empty($totals)) : ?>
    <tfoot>
      <tr <?php if (!empty($totals_row_class)): ?>class="<?php print $totals_row_class; ?>"<?php endif ?>>
        <?php
          // Use order of the row fields to output the totals likewise.
          foreach (array_keys(reset($rows)) as $field):
            $fclasses = isset($field_classes[$field]) ? reset($field_classes[$field]) : '';
            if ($field === $grouping_field) {
              $fclasses .= " $grouping_field_class";
            }
        ?>
          <th <?php if (!empty($fclasses)): ?>class="<?php print $fclasses; ?>"<?php endif ?>>
            <?php print isset($totals[$field]) ? $totals[$field] : ''; ?>
          </th>
        <?php endforeach ?>
      </tr>
    </tfoot>
  <?php endif ?>
</table>

<?php
if (($library = libraries_load('d3')) && !empty($library['loaded'])) {
  drupal_add_css(
    '.slice:hover { stroke: black; }',
    array('type' => 'inline')
);
drupal_add_js('
  var tpj=jQuery;
  tpj.noConflict();
  
  var rows = ' . drupal_json_encode($rows) . ';
  var valores = [];
  tpj.each(rows, function(k,d) {
    var row = []
    var valor =  d.field_valor.replace(/(\.|€)/g,"");
    valor = valor.replace(",",".");
    var producto = d.field_producto;
    console.log(valor);
    row["producto"] = producto;
    row["valor"] = valor;
    valores.push(row);
  });

  valores.map(function(d) {
    console.log(d.producto + " - " + d.valor);
  });
   
  tpj(document).ready(function($) {
    var pie = d3.layout.pie()
      .sort(function(d) {
        return d.valor;
      })
      .value(function(d) {
        return d.valor;
      });
    var color = d3.scale.category10();
    var w = 500;
    var h = 500;

    var outerRadius = w / 4;
    var innerRadius = 0;
    var arc = d3.svg.arc().innerRadius(innerRadius).outerRadius(outerRadius);

    var svg = d3.select("#main-wrapper")
                .append("svg")
                .attr("width", w)
                .attr("height", h);

    var arcs = svg.selectAll("g.arc")
                  .data(pie(valores))
                  .enter()
                  .append("g")
                  .attr("class", "sliceXXX")
                  .attr("transform", "translate(" + (w - outerRadius) + ", " + outerRadius + ")");

    arcs.append("path")
        .attr("fill", function(d, i) { return color(i); })
        .attr("d", arc);

    /*arcs.append("text")
        .attr("transform", function(d) {
          return "translate(" + arc.centroid(d) + ")";
        })
        .attr("fill", "white")
        .text(function(d) {
          return d.data.producto;
      });*/


    /*var svg2 = d3.select("#main-wrapper")
                 .append("svg")
                 .attr("width", w)
                 .attr("height", h);*/

    var rects = svg.selectAll("rect")
                    .data(valores)
                    .enter()
                    .append("rect")

    var captions = svg.selectAll("text")
                       .data(valores)
                       .enter()
                       .append("text");

    rects.attr("fill", function(d,i) { return color(i); })
         .attr("x", 10)
         .attr("y", function(d, i) { return 30 * i; })
         .attr("width", 24)
         .attr("height", 24);

    captions.attr("fill", "black")
            .text(function(d) { return d.producto; })
            .attr("x", 44)
            .attr("y", function(d, i) { return (30 * i) + 18; });

  });',
    array('type' => 'inline', 'scope' => 'header'));
}
?>
