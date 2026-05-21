<?php
namespace UsingRefs;

class ProductController
{

    /**
     * @OA\Get(
     *   tags={"Products"},
     *   path="/products/{product_id}/do-stuff",
     *   @OA\Parameter(ref="#/components/schemas/Product/properties/id"),
     *   @OA\Response(
     *       response="default",
     *       ref="#/components/responses/product"
     *   )
     * )
     */
    public function doStuff($id)
    {
    }
}
